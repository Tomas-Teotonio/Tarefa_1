<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    order: Object,
})

const formatPrice = (value) => {
    return Number(value).toFixed(2) + ' €'
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('pt-PT')
}
</script>

<template>
    <AppLayout :title="`Encomenda ${order.number}`">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="mb-3 flex flex-wrap gap-2">
                    <div class="badge badge-primary badge-outline">
                        Detalhe da Encomenda
                    </div>

                    <div
                        class="badge"
                        :class="{
                            'badge-warning': order.status === 'pending',
                            'badge-success': order.status === 'paid',
                            'badge-error': order.status === 'cancelled',
                        }"
                    >
                        <span v-if="order.status === 'pending'">Pendente</span>
                        <span v-else-if="order.status === 'paid'">Paga</span>
                        <span v-else>Cancelada</span>
                    </div>
                </div>

                <h1 class="text-3xl font-bold">
                    Encomenda {{ order.number }}
                </h1>

                <p class="mt-2 text-sm opacity-70">
                    Criada em {{ formatDateTime(order.created_at) }}
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">

                <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                    <h2 class="text-xl font-bold">
                        Cidadão
                    </h2>

                    <div class="mt-4 space-y-1 text-sm">
                        <p class="font-semibold">
                            {{ order.user?.name }}
                        </p>

                        <p class="opacity-70">
                            {{ order.user?.email }}
                        </p>
                    </div>
                </div>

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
                    <h2 class="text-xl font-bold">
                        Pagamento
                    </h2>

                    <div class="mt-4 space-y-2 text-sm">
                        <p>
                            <strong>Total:</strong>
                            {{ formatPrice(order.total) }}
                        </p>

                        <p>
                            <strong>Pago em:</strong>
                            {{ formatDateTime(order.paid_at) }}
                        </p>

                        <p class="break-all">
                            <strong>Sessão Stripe:</strong>
                            {{ order.stripe_checkout_session_id ?? '-' }}
                        </p>
                    </div>
                </div>

            </div>

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h2 class="text-xl font-bold">
                    Livros da encomenda
                </h2>

                <div class="mt-5 overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Livro</th>
                                <th>ISBN</th>
                                <th>Preço</th>
                                <th>Quantidade</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="item in order.items"
                                :key="item.id"
                            >
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="item.book?.cover_image"
                                            :src="item.book.cover_image.startsWith('http')
                                                ? item.book.cover_image
                                                : '/storage/' + item.book.cover_image"
                                            class="h-16 w-12 rounded object-cover"
                                        />

                                        <div>
                                            <p class="font-semibold">
                                                {{ item.book_name }}
                                            </p>

                                            <p class="text-xs opacity-60">
                                                ID Livro: {{ item.book_id ?? '-' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    {{ item.book_isbn ?? '-' }}
                                </td>

                                <td>
                                    {{ formatPrice(item.unit_price) }}
                                </td>

                                <td>
                                    {{ item.quantity }}
                                </td>

                                <td class="font-bold">
                                    {{ formatPrice(item.total) }}
                                </td>
                            </tr>

                            <tr v-if="!order.items?.length">
                                <td colspan="5" class="text-center opacity-60">
                                    Esta encomenda não tem itens.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <Link
                    :href="route('admin.orders.index')"
                    class="btn btn-outline"
                >
                    Voltar às encomendas
                </Link>
            </div>

        </div>
    </AppLayout>
</template>