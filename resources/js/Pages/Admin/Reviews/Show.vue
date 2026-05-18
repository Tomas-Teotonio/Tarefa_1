<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    review: Object
})

const form = useForm({
    status: props.review.status === 'refused' ? 'refused' : 'active',
    refusal_reason: props.review.refusal_reason ?? ''
})

const submit = () => {
    form.put(route('admin.reviews.updateStatus', props.review.id), {
        preserveScroll: true
    })
}
</script>

<template>
    <AppLayout title="Detalhe da Review">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h1 class="text-3xl font-bold">
                    Detalhe da Review
                </h1>

                <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                    <p><strong>Livro:</strong> {{ review.book?.name }}</p>
                    <p><strong>Cidadão:</strong> {{ review.user?.name }}</p>
                    <p><strong>Estado atual:</strong> {{ review.status }}</p>
                    <p><strong>Criada em:</strong> {{ review.created_at }}</p>
                </div>
            </div>

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h2 class="text-xl font-bold">
                    Conteúdo
                </h2>

                <p class="mt-4 whitespace-pre-line">
                    {{ review.content }}
                </p>
            </div>

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h2 class="text-xl font-bold">
                    Moderação
                </h2>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="label">Estado</label>

                        <select
                            v-model="form.status"
                            class="select select-bordered w-full"
                        >
                            <option value="active">Ativo</option>
                            <option value="refused">Recusado</option>
                        </select>
                    </div>

                    <div v-if="form.status === 'refused'">
                        <label class="label">Justificação</label>

                        <textarea
                            v-model="form.refusal_reason"
                            class="textarea textarea-bordered w-full"
                            rows="4"
                            placeholder="Indica a justificação da recusa..."
                        />

                        <p v-if="form.errors.refusal_reason" class="text-sm text-error mt-1">
                            {{ form.errors.refusal_reason }}
                        </p>
                    </div>

                    <button
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        Guardar estado
                    </button>
                </form>
            </div>

        </div>
    </AppLayout>
</template>