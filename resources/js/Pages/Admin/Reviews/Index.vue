<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    reviews: Array
})
</script>

<template>
    <AppLayout title="Reviews">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h1 class="text-3xl font-bold">
                    Reviews
                </h1>

                <p class="mt-2 text-sm opacity-70">
                    Lista de reviews submetidas pelos cidadãos.
                </p>
            </div>

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Livro</th>
                                <th>Cidadão</th>
                                <th>Estado</th>
                                <th>Ação</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="review in reviews" :key="review.id">
                                <td>{{ review.book?.name }}</td>
                                <td>{{ review.user?.name }}</td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="{
                                            'badge-warning': review.status === 'suspended',
                                            'badge-success': review.status === 'active',
                                            'badge-error': review.status === 'refused',
                                        }"
                                    >
                                        {{ review.status }}
                                    </span>
                                </td>
                                <td>
                                    <Link
                                        :href="route('admin.reviews.show', review.id)"
                                        class="btn btn-sm btn-primary"
                                    >
                                        Ver
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!reviews.length">
                                <td colspan="4" class="text-center opacity-60">
                                    Sem reviews
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>