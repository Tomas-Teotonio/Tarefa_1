<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
    request: Object
})

const page = usePage()
const user = page.props.auth.user

const form = useForm({
    content: ''
})

const submit = () => {
    form.post(route('requests.reviews.store', props.request.id), {
        preserveScroll: true,
        onSuccess: () => form.reset()
    })
}
</script>

<template>
    <AppLayout title="Detalhe da Requisição">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h1 class="text-2xl font-bold">
                    Requisição {{ request.number }}
                </h1>

                <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                    <p><strong>Livro:</strong> {{ request.book?.name }}</p>
                    <p><strong>Cidadão:</strong> {{ request.user?.name }}</p>
                    <p><strong>Data:</strong> {{ request.request_date }}</p>
                    <p><strong>Entrega prevista:</strong> {{ request.expected_return_date }}</p>
                    <p><strong>Devolução:</strong> {{ request.actual_return_date ?? '-' }}</p>
                    <p><strong>Estado:</strong> {{ request.status }}</p>
                </div>
            </div>

            <div
                v-if="request.status === 'returned' && user.role === 'citizen' && !request.review"
                class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm"
            >
                <h2 class="text-xl font-bold">
                    Deixar review
                </h2>

                <p class="mt-1 text-sm opacity-70">
                    A review ficará suspensa até aprovação de um admin.
                </p>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <textarea
                        v-model="form.content"
                        class="textarea textarea-bordered w-full"
                        rows="5"
                        placeholder="Escreve a tua opinião sobre o livro..."
                    />

                    <p v-if="form.errors.content" class="text-sm text-error">
                        {{ form.errors.content }}
                    </p>

                    <button
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        Enviar review
                    </button>
                </form>
            </div>

            <div
                v-if="request.review"
                class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm"
            >
                <h2 class="text-xl font-bold">
                    Review enviada
                </h2>

                <div class="mt-3">
                    <div
                        class="badge"
                        :class="{
                            'badge-warning': request.review.status === 'suspended',
                            'badge-success': request.review.status === 'active',
                            'badge-error': request.review.status === 'refused',
                        }"
                    >
                        {{ request.review.status }}
                    </div>
                </div>

                <p class="mt-4 whitespace-pre-line">
                    {{ request.review.content }}
                </p>

                <div
                    v-if="request.review.status === 'refused'"
                    class="alert alert-error mt-4"
                >
                    <span>
                        <strong>Justificação:</strong>
                        {{ request.review.refusal_reason }}
                    </span>
                </div>
            </div>

            <div
                v-if="request.status !== 'returned'"
                class="alert alert-info"
            >
                <span>
                    Só podes deixar review depois da devolução do livro.
                </span>
            </div>

        </div>
    </AppLayout>
</template>