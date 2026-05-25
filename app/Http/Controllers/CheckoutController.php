<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    public function address()
    {
        $user = Auth::user();

        if (!$user->isCitizen()) {
            abort(403);
        }

        $items = CartItem::with('book')
            ->where('user_id', $user->id)
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'O carrinho está vazio.');
        }

        $total = $items->sum(function ($item) {
            return (float) $item->book->price * $item->quantity;
        });

        return Inertia::render('Checkout/Address', [
            'items' => $items,
            'total' => $total,
            'user' => $user,
        ]);
    }

    public function storeAddress(Request $request)
    {
        $user = Auth::user();

        if (!$user->isCitizen()) {
            abort(403);
        }

        $data = $request->validate([
            'delivery_name' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'delivery_city' => ['required', 'string', 'max:255'],
            'delivery_postal_code' => ['required', 'string', 'max:50'],
            'delivery_country' => ['required', 'string', 'max:255'],
        ]);

        $items = CartItem::with('book')
            ->where('user_id', $user->id)
            ->get();

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'O carrinho está vazio.');
        }

        $order = DB::transaction(function () use ($user, $items, $data) {
            $total = $items->sum(function ($item) {
                return (float) $item->book->price * $item->quantity;
            });

            $order = Order::create([
                'number' => 'TEMP',
                'user_id' => $user->id,
                'status' => 'pending',
                'delivery_name' => $data['delivery_name'],
                'delivery_address' => $data['delivery_address'],
                'delivery_city' => $data['delivery_city'],
                'delivery_postal_code' => $data['delivery_postal_code'],
                'delivery_country' => $data['delivery_country'],
                'total' => $total,
            ]);

            $order->number = 'ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT);
            $order->save();

            foreach ($items as $item) {
                $unitPrice = (float) $item->book->price;
                $lineTotal = $unitPrice * $item->quantity;

                $order->items()->create([
                    'book_id' => $item->book_id,
                    'book_name' => $item->book->name,
                    'book_isbn' => $item->book->isbn,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'total' => $lineTotal,
                ]);
            }

            CartItem::where('user_id', $user->id)->delete();

            return $order;
        });

        return redirect()
            ->route('checkout.payment', $order)
            ->with('success', 'Encomenda criada. Continua para pagamento.');
    }

    public function payment(Order $order)
    {
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        $order->load(['items.book']);

        return Inertia::render('Checkout/Payment', [
            'order' => $order,
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    public function startPayment(Order $order)
    {
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        if ($order->status === 'paid') {
            return redirect()
                ->route('checkout.success', $order)
                ->with('success', 'Esta encomenda já se encontra paga.');
        }

        $order->load('items');

        if ($order->items->isEmpty()) {
            return back()->with('error', 'A encomenda não tem itens.');
        }

        if ((float) $order->total <= 0) {
            return back()->with('error', 'A encomenda tem total 0€. Define preço nos livros antes de pagar.');
        }

        $lineItems = $order->items->map(function ($item) {
            $unitAmount = (int) round((float) $item->unit_price * 100);

            if ($unitAmount <= 0) {
                $unitAmount = 1;
            }

            return [
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'eur'),
                    'product_data' => [
                        'name' => $item->book_name,
                        'metadata' => [
                            'book_id' => $item->book_id,
                        ],
                    ],
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => $item->quantity,
            ];
        })->values()->toArray();

        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $user->email,
            'line_items' => $lineItems,

            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->number,
                'user_id' => $user->id,
            ],

            'success_url' => route('checkout.success', $order) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel', $order),
        ]);

        $order->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        return Inertia::location($session->url);
    }

    public function success(Request $request, Order $order)
    {
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()
                ->route('checkout.payment', $order)
                ->with('error', 'Sessão de pagamento inválida.');
        }

        if ($order->stripe_checkout_session_id !== $sessionId) {
            return redirect()
                ->route('checkout.payment', $order)
                ->with('error', 'Sessão de pagamento não corresponde à encomenda.');
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->retrieve($sessionId);

        if ($session->payment_status === 'paid') {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        $order->load('items');

        return Inertia::render('Checkout/Success', [
            'order' => $order,
            'paymentStatus' => $session->payment_status,
        ]);
    }

    public function cancel(Order $order)
    {
        $user = Auth::user();

        if ($order->user_id !== $user->id) {
            abort(403);
        }

        return redirect()
            ->route('checkout.payment', $order)
            ->with('error', 'Pagamento cancelado. A encomenda continua pendente.');
    }
}