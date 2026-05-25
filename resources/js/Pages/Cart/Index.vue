<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    items: Array,
    total: Number,
})

const formatPrice = (value) => {
    return Number(value).toFixed(2) + ' €'
}

const updateQuantity = (item, quantity) => {
    router.put(route('cart.update', item.id), {
        quantity: quantity,
    }, {
        preserveScroll: true,
    })
}

const removeItem = (item) => {
    if (confirm('Remover este livro do carrinho?')) {
        router.delete(route('cart.destroy', item.id), {
            preserveScroll: true,
        })
    }
}

const clearCart = () => {
    if (confirm('Limpar todo o carrinho?')) {
        router.delete(route('cart.clear'), {
            preserveScroll: true,
        })
    }
}
</script>

<template>
    <AppLayout title="Carrinho">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h1 class="text-3xl font-bold">
                    Carrinho de compras
                </h1>

                <p class="mt-2 text-sm opacity-70">
                    Lista de livros adicionados ao carrinho.
                </p>
            </div>

            <div
                v-if="items.length"
                class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Livro</th>
                                <th>Preço</th>
                                <th>Quantidade</th>
                                <th>Total</th>
                                <th>Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="item in items"
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
                                                {{ item.book?.name }}
                                            </p>

                                            <p class="text-xs opacity-60">
                                                {{ item.book?.publisher?.name ?? '-' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    {{ formatPrice(item.book?.price) }}
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        min="1"
                                        max="20"
                                        class="input input-bordered input-sm w-20"
                                        :value="item.quantity"
                                        @change="updateQuantity(item, Number($event.target.value))"
                                    />
                                </td>

                                <td class="font-bold">
                                    {{ formatPrice(item.line_total) }}
                                </td>

                                <td>
                                    <button
                                        class="btn btn-sm btn-error"
                                        @click="removeItem(item)"
                                    >
                                        Remover
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <button
                        class="btn btn-outline btn-error"
                        @click="clearCart"
                    >
                        Limpar carrinho
                    </button>

                    <div class="text-right">
                        <p class="text-sm opacity-70">
                            Total
                        </p>

                        <p class="text-3xl font-bold">
                            {{ formatPrice(total) }}
                        </p>

                        <Link
                            :href="route('checkout.address')"
                            class="btn btn-primary mt-3"
                        >
                            Continuar para morada
                        </Link>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-3xl border border-base-300 bg-base-100 p-10 text-center shadow-sm"
            >
                <p class="opacity-60">
                    O carrinho está vazio.
                </p>

                <Link
                    :href="route('books.index')"
                    class="btn btn-primary mt-4"
                >
                    Ver livros
                </Link>
            </div>

        </div>
    </AppLayout>
</template>