<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    order: Object,
    stripeKey: String,
})

const loading = ref(false)

const formatPrice = (value) => {
    return Number(value).toFixed(2) + ' €'
}

const pay = () => {
    loading.value = true

    router.post(route('checkout.payment.start', props.order.id), {}, {
        onFinish: () => {
            loading.value = false
        }
    })
}
</script>

<template>
    <AppLayout title="Pagamento">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div
                    class="badge mb-3"
                    :class="order.status === 'paid' ? 'badge-success' : 'badge-warning badge-outline'"
                >
                    {{ order.status === 'paid' ? 'Pago' : 'Pagamento pendente' }}
                </div>

                <h1 class="text-3xl font-bold">
                    Encomenda {{ order.number }}
                </h1>

                <p class="mt-2 text-sm opacity-70">
                    Confirma os dados da encomenda e avança para pagamento Stripe.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">

                <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                    <h2 class="text-xl font-bold">
                        Livros da encomenda
                    </h2>

                    <div class="mt-5 overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Livro</th>
                                    <th>Preço</th>
                                    <th>Qtd.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="item in order.items"
                                    :key="item.id"
                                >
                                    <td>
                                        <p class="font-semibold">
                                            {{ item.book_name }}
                                        </p>

                                        <p class="text-xs opacity-60">
                                            ISBN {{ item.book_isbn ?? '-' }}
                                        </p>
                                    </td>

                                    <td>{{ formatPrice(item.unit_price) }}</td>
                                    <td>{{ item.quantity }}</td>
                                    <td class="font-bold">{{ formatPrice(item.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                        <h2 class="text-xl font-bold">
                            Morada de entrega
                        </h2>

                        <div class="mt-4 space-y-1 text-sm">
                            <p class="font-semibold">
                                {{ order.delivery_name }}
                            </p>

                            <p>{{ order.delivery_address }}</p>
                            <p>{{ order.delivery_postal_code }} {{ order.delivery_city }}</p>
                            <p>{{ order.delivery_country }}</p>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                        <p class="text-sm opacity-70">
                            Total
                        </p>

                        <p class="text-3xl font-bold">
                            {{ formatPrice(order.total) }}
                        </p>

                        <button
                            v-if="order.status !== 'paid'"
                            class="btn btn-primary mt-4 w-full"
                            :disabled="loading"
                            @click="pay"
                        >
                            <span
                                v-if="loading"
                                class="loading loading-spinner loading-sm"
                            ></span>

                            <span v-else>
                                Pagar com Stripe
                            </span>
                        </button>

                        <Link
                            v-else
                            :href="route('books.index')"
                            class="btn btn-success mt-4 w-full"
                        >
                            Encomenda paga
                        </Link>

                        <Link
                            :href="route('books.index')"
                            class="btn btn-outline mt-3 w-full"
                        >
                            Voltar aos livros
                        </Link>

                        <p class="mt-4 text-xs opacity-60">
                            Usa Stripe sandbox. Cartão de teste: 4242 4242 4242 4242.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>