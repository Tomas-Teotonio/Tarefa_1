<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    order: Object,
    paymentStatus: String,
})

const formatPrice = (value) => {
    return Number(value).toFixed(2) + ' €'
}
</script>

<template>
    <AppLayout title="Pagamento concluído">
        <div class="space-y-6">

            <div class="rounded-3xl border border-success bg-base-100 p-8 text-center shadow-sm">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-success text-success-content">
                    ✓
                </div>

                <h1 class="text-3xl font-bold">
                    Pagamento concluído
                </h1>

                <p class="mt-2 opacity-70">
                    A tua encomenda {{ order.number }} foi processada.
                </p>
            </div>

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-sm opacity-60">Estado Stripe</p>
                        <p class="font-bold">{{ paymentStatus }}</p>
                    </div>

                    <div>
                        <p class="text-sm opacity-60">Estado encomenda</p>
                        <p class="font-bold">{{ order.status }}</p>
                    </div>

                    <div>
                        <p class="text-sm opacity-60">Total</p>
                        <p class="font-bold">{{ formatPrice(order.total) }}</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <Link
                    :href="route('books.index')"
                    class="btn btn-primary"
                >
                    Voltar aos livros
                </Link>
            </div>

        </div>
    </AppLayout>
</template>