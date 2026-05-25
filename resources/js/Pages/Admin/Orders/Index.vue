<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    orders: Array,
    stats: Object,
})

const formatPrice = (value) => {
    return Number(value).toFixed(2) + ' €'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('pt-PT')
}
</script>

<template>
    <AppLayout title="Encomendas">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h1 class="text-3xl font-bold">
                    Encomendas
                </h1>

                <p class="mt-2 text-sm opacity-70">
                    Lista de encomendas efetuadas pelos cidadãos.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm">
                    <p class="text-sm opacity-60">Total</p>
                    <p class="text-3xl font-bold">{{ stats.total }}</p>
                </div>

                <div class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm">
                    <p class="text-sm opacity-60">Pendentes</p>
                    <p class="text-3xl font-bold text-warning">{{ stats.pending }}</p>
                </div>

                <div class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm">
                    <p class="text-sm opacity-60">Pagas</p>
                    <p class="text-3xl font-bold text-success">{{ stats.paid }}</p>
                </div>
            </div>

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Cidadão</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Data</th>
                                <th>Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="order in orders"
                                :key="order.id"
                            >
                                <td class="font-semibold">
                                    {{ order.number }}
                                </td>

                                <td>
                                    <div>
                                        <p class="font-medium">
                                            {{ order.user?.name }}
                                        </p>

                                        <p class="text-xs opacity-60">
                                            {{ order.user?.email }}
                                        </p>
                                    </div>
                                </td>

                                <td class="font-bold">
                                    {{ formatPrice(order.total) }}
                                </td>

                                <td>
                                    <span
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
                                    </span>
                                </td>

                                <td>
                                    {{ formatDate(order.created_at) }}
                                </td>

                                <td>
                                    <Link
                                        :href="route('admin.orders.show', order.id)"
                                        class="btn btn-sm btn-primary"
                                    >
                                        Ver detalhe
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!orders.length">
                                <td colspan="6" class="text-center opacity-60">
                                    Ainda não existem encomendas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>