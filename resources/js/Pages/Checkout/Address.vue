<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    items: Array,
    total: Number,
    user: Object,
})

const form = useForm({
    delivery_name: props.user?.name ?? '',
    delivery_address: '',
    delivery_city: '',
    delivery_postal_code: '',
    delivery_country: 'Portugal',
})

const formatPrice = (value) => {
    return Number(value).toFixed(2) + ' €'
}

const submit = () => {
    form.post(route('checkout.address.store'))
}
</script>

<template>
    <AppLayout title="Morada de Entrega">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h1 class="text-3xl font-bold">
                    Morada de entrega
                </h1>

                <p class="mt-2 text-sm opacity-70">
                    Preenche os dados de entrega para criar a encomenda.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">

                <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                    <form class="space-y-4" @submit.prevent="submit">

                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Nome</span>
                            </label>

                            <input
                                v-model="form.delivery_name"
                                type="text"
                                class="input input-bordered w-full"
                            />

                            <p v-if="form.errors.delivery_name" class="mt-1 text-sm text-error">
                                {{ form.errors.delivery_name }}
                            </p>
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Morada</span>
                            </label>

                            <input
                                v-model="form.delivery_address"
                                type="text"
                                class="input input-bordered w-full"
                                placeholder="Rua, número, andar..."
                            />

                            <p v-if="form.errors.delivery_address" class="mt-1 text-sm text-error">
                                {{ form.errors.delivery_address }}
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="label">
                                    <span class="label-text font-medium">Cidade</span>
                                </label>

                                <input
                                    v-model="form.delivery_city"
                                    type="text"
                                    class="input input-bordered w-full"
                                />

                                <p v-if="form.errors.delivery_city" class="mt-1 text-sm text-error">
                                    {{ form.errors.delivery_city }}
                                </p>
                            </div>

                            <div>
                                <label class="label">
                                    <span class="label-text font-medium">Código postal</span>
                                </label>

                                <input
                                    v-model="form.delivery_postal_code"
                                    type="text"
                                    class="input input-bordered w-full"
                                    placeholder="0000-000"
                                />

                                <p v-if="form.errors.delivery_postal_code" class="mt-1 text-sm text-error">
                                    {{ form.errors.delivery_postal_code }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text font-medium">País</span>
                            </label>

                            <input
                                v-model="form.delivery_country"
                                type="text"
                                class="input input-bordered w-full"
                            />

                            <p v-if="form.errors.delivery_country" class="mt-1 text-sm text-error">
                                {{ form.errors.delivery_country }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3 pt-4">
                            <Link
                                :href="route('cart.index')"
                                class="btn btn-outline"
                            >
                                Voltar ao carrinho
                            </Link>

                            <button
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                Criar encomenda
                            </button>
                        </div>

                    </form>
                </div>

                <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                    <h2 class="text-xl font-bold">
                        Resumo
                    </h2>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="flex justify-between gap-3 text-sm"
                        >
                            <div>
                                <p class="font-medium">
                                    {{ item.book?.name }}
                                </p>

                                <p class="opacity-60">
                                    {{ item.quantity }} × {{ formatPrice(item.book?.price) }}
                                </p>
                            </div>

                            <p class="font-semibold">
                                {{ formatPrice(Number(item.book?.price) * item.quantity) }}
                            </p>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="flex justify-between text-lg font-bold">
                        <span>Total</span>
                        <span>{{ formatPrice(total) }}</span>
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>