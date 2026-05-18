<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

const props = defineProps({
    requests: Array,
    stats: Object
})

const page = usePage()
const user = page.props.auth.user

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('pt-PT')
}

const returnBook = (id) => {
    if (confirm('Confirmar devolução do livro?')) {
        router.post(route('requests.return', id))
    }
}

const showModal = ref(false)
const selectedRequest = ref(null)
const photo = ref()

const openReturnModal = (request) => {
    selectedRequest.value = request
    showModal.value = true
}

const submitReturn = () => {
    const formData = new FormData()
    formData.append('photo', photo.value)

    router.post(
        route('requests.return', selectedRequest.value.id),
        formData,
        {
            forceFormData: true,
            onSuccess: () => {
                showModal.value = false
                photo.value = undefined
            }
        }
    )
}

const handleFileChange = (event) => {
    const file = event.target.files[0]
    if (file) {
        photo.value = file
    }
}

const showProofModal = ref(false)
const selectedProof = ref(null)

const openProofModal = (request) => {
    selectedProof.value = request
    showProofModal.value = true
}
</script>

<template>
    <AppLayout title="Requisições">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            <div class="p-4 bg-base-100 rounded-xl shadow">
                <div class="text-sm opacity-70">Ativas</div>
                <div class="text-3xl font-bold text-primary">
                    {{ stats.active }}
                </div>
            </div>

            <div class="p-4 bg-base-100 rounded-xl shadow">
                <div class="text-sm opacity-70">Últimos 30 dias</div>
                <div class="text-3xl font-bold text-secondary">
                    {{ stats.last30days }}
                </div>
            </div>

            <div class="p-4 bg-base-100 rounded-xl shadow">
                <div class="text-sm opacity-70">Entregues hoje</div>
                <div class="text-3xl font-bold text-success">
                    {{ stats.returnedToday }}
                </div>
            </div>

        </div>

        <div class="bg-base-100 p-6 rounded-2xl shadow">

            <h1 class="text-xl font-bold mb-4">Requisições</h1>

            <table class="table w-full table-fixed">
                <thead>
                <tr>
                    <th class="w-24">Nº</th>
                    <th class="w-40">Livro</th>
                    <th class="w-20">Utilizador</th>
                    <th>Data</th>
                    <th>Entrega</th>
                    <th>Devolução</th>
                    <th class="w-20">Dias</th>
                    <th class="w-24">Estado</th>
                    <th class="w-28">Ação</th>
                </tr>
                </thead>

                <tbody>
                    <tr v-for="req in requests" :key="req.id">

                        <td>
                            <a
                                :href="route('requests.show', req.id)"
                                class="link link-primary font-medium"
                            >
                                {{ req.number }}
                            </a>
                        </td>

                        <td class="truncate">
                            {{ req.book?.name }}
                        </td>

                        <td>
                            <a
                                :href="route('users.show', req.user.id)"
                                class=" text-blue-600 hover:underline"
                            >
                                {{ req.user?.name }}
                            </a>
                        </td>

                        <td>{{ formatDate(req.request_date) }}</td>

                        <td>{{ formatDate(req.expected_return_date) }}</td>

                        <td>{{ formatDate(req.actual_return_date) }}</td>

                        <td>
                            <span v-if="req.days_used !== null">
                                {{ Math.floor(req.days_used) }} dias
                            </span>
                            <span v-else>-</span>
                        </td>

                        <td>
                            <span
                                class="badge"
                                :class="req.status === 'active'
                                    ? 'badge-warning'
                                    : 'badge-success'"
                            >
                                {{ req.status === 'active' ? 'Ativo' : 'Devolvido' }}
                            </span>
                        </td>

                        <td>

                            <button
                                v-if="user.role === 'admin' && req.status === 'active'"
                                class="btn btn-sm btn-accent"
                                @click="openReturnModal(req)"
                            >
                                Devolver
                            </button>

                            <button
                                v-else-if="req.status === 'returned' && req.user_photo_url"
                                class="btn btn-sm btn-success"
                                @click="openProofModal(req)"
                            >
                                Prova
                            </button>

                            <span v-else class="opacity-50 text-sm">
                                —
                            </span>

                        </td>

                    </tr>

                    <tr v-if="!requests.length">
                        <td colspan="9" class="text-center opacity-60">
                            Sem requisições
                        </td>
                    </tr>

                </tbody>
            </table>

        </div>

        <div v-if="showModal" class="modal modal-open">
            <div class="modal-box">

                <h3 class="font-bold text-lg">
                    Confirmar devolução
                </h3>

                <p class="py-2 text-sm opacity-70">
                    Adiciona uma foto como prova da entrega do livro.
                </p>

                <input
                    type="file"
                    class="file-input file-input-bordered w-full mt-4"
                    @change="handleFileChange"
                />

                <p v-if="photo" class="text-sm text-green-600 mt-2">
                    ✔ {{ photo.name }}
                </p>

                <div class="modal-action">
                    <button class="btn" @click="showModal = false">
                        Cancelar
                    </button>

                    <button
                        class="btn"
                        :class="photo ? 'btn-primary' : 'btn-disabled opacity-50 cursor-not-allowed'"
                        :disabled="!photo"
                        @click="submitReturn"
                    >
                        Confirmar Devolução
                    </button>
                </div>

            </div>
        </div>

        <div v-if="showProofModal" class="modal modal-open">
            <div class="modal-box max-w-lg">

                <h3 class="font-bold text-lg mb-3">
                    Prova de Devolução
                </h3>

                <img
                    :src="selectedProof.user_photo_url"
                    class="w-full rounded-lg mb-4"
                />

                <div class="text-sm space-y-1">
                    <p><strong>Livro:</strong> {{ selectedProof.book?.name }}</p>
                    <p><strong>Utilizador:</strong> {{ selectedProof.user?.name }}</p>
                    <p><strong>Data devolução:</strong> {{ formatDate(selectedProof.actual_return_date) }}</p>
                    <p><strong>Dias usados:</strong> {{ selectedProof.days_used }} dias</p>
                </div>

                <div class="modal-action">
                    <button class="btn" @click="showProofModal = false">
                        Fechar
                    </button>
                </div>

            </div>
        </div>

    </AppLayout>
</template>