<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { usePage, router } from '@inertiajs/vue3'

const props = defineProps({
    book: Object
})

const page = usePage()
const user = page.props.auth.user

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('pt-PT')
}

const requestBook = () => {
    router.post(route('books.request', props.book.id))
}
</script>

<template>
    <AppLayout :title="book.name">

        <div class="bg-base-100 p-6 rounded-2xl shadow mb-6">

            <div class="flex justify-between items-start">

                <div>
                    <h1 class="text-2xl font-bold mb-2">{{ book.name }}</h1>

                    <p><b>ISBN:</b> {{ book.isbn }}</p>
                    <p><b>Editora:</b> {{ book.publisher?.name }}</p>

                    <div class="mt-2">
                        <b>Autores:</b>
                        <span
                            v-for="author in book.authors"
                            :key="author.id"
                            class="badge badge-outline ml-1"
                        >
                            {{ author.name }}
                        </span>
                    </div>
                </div>

                <div class="text-right">

                    <div class="mb-2">
                        <span
                            class="badge"
                            :class="book.is_available ? 'badge-success' : 'badge-error'"
                        >
                            {{ book.is_available ? 'Disponível' : 'Indisponível' }}
                        </span>
                    </div>

                    <button
                        class="btn btn-primary btn-sm"
                        :disabled="!book.is_available"
                        @click="requestBook"
                    >
                        Requisitar
                    </button>

                </div>

            </div>
        </div>

        <div class="bg-base-100 p-6 rounded-2xl shadow">

            <h2 class="text-xl font-bold mb-4">Histórico de Requisições</h2>

            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Nº</th>
                        <th>Utilizador</th>
                        <th>Data</th>
                        <th>Prevista</th>
                        <th>Devolução</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="req in book.requests" :key="req.id">
                        <td>{{ req.number }}</td>
                        <td>{{ req.user?.name }}</td>

                        <td>{{ formatDate(req.request_date) }}</td>
                        <td>{{ formatDate(req.expected_return_date) }}</td>
                        <td>{{ formatDate(req.actual_return_date) }}</td>

                        <td>
                            <span
                                class="badge"
                                :class="req.status === 'active' ? 'badge-warning' : 'badge-success'"
                            >
                                {{ req.status === 'active' ? 'Ativo' : 'Devolvido' }}
                            </span>
                        </td>
                    </tr>

                    <tr v-if="!book.requests.length">
                        <td colspan="6" class="text-center opacity-60">
                            Sem histórico
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>

    </AppLayout>
</template>