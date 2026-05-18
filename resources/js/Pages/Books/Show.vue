<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    book: Object,
    relatedBooks: Array,
    hasAvailabilityAlert: Boolean,
})

const page = usePage()
const user = page.props.auth.user

const loadingRequest = ref(false)
const loadingAlert = ref(false)
const hasAlert = ref(props.hasAvailabilityAlert ?? false)

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('pt-PT')
}

const requestBook = () => {
    loadingRequest.value = true

    router.post(route('books.request', props.book.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            loadingRequest.value = false
        }
    })
}

const createAvailabilityAlert = () => {
    loadingAlert.value = true

    router.post(route('books.availability-alert.store', props.book.id), {}, {
        preserveScroll: true,

        onSuccess: () => {
            hasAlert.value = true
        },

        onFinish: () => {
            loadingAlert.value = false
        }
    })
}
</script>

<template>
    <AppLayout :title="book.name">
        <div class="space-y-6">

            <!-- INFO LIVRO -->
            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)]">

                    <div>
                        <img
                            v-if="book.cover_image"
                            :src="book.cover_image.startsWith('http') ? book.cover_image : '/storage/' + book.cover_image"
                            class="h-80 w-full rounded-2xl object-cover"
                        />

                        <div
                            v-else
                            class="flex h-80 w-full items-center justify-center rounded-2xl bg-base-200 text-sm opacity-60"
                        >
                            Sem capa
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 flex flex-wrap gap-2">
                            <div class="badge badge-primary badge-outline">
                                Detalhe do Livro
                            </div>

                            <div
                                class="badge"
                                :class="book.is_available ? 'badge-success' : 'badge-error'"
                            >
                                {{ book.is_available ? 'Disponível' : 'Indisponível' }}
                            </div>
                        </div>

                        <h1 class="text-3xl font-bold">
                            {{ book.name }}
                        </h1>

                        <div class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                            <p>
                                <strong>ISBN:</strong>
                                {{ book.isbn }}
                            </p>

                            <p>
                                <strong>Editora:</strong>
                                {{ book.publisher?.name ?? '-' }}
                            </p>

                            <p>
                                <strong>Preço:</strong>
                                {{ Number(book.price).toFixed(2) }} €
                            </p>

                            <div>
                                <strong>Autores:</strong>

                                <div class="mt-1 flex flex-wrap gap-1">
                                    <span
                                        v-for="author in book.authors"
                                        :key="author.id"
                                        class="badge badge-outline"
                                    >
                                        {{ author.name }}
                                    </span>

                                    <span v-if="!book.authors?.length">
                                        -
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-if="book.bibliography" class="mt-6">
                            <h2 class="font-bold">
                                Bibliografia / Descrição
                            </h2>

                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed opacity-80">
                                {{ book.bibliography }}
                            </p>
                        </div>

                        <div class="mt-6">
                            <button
                                v-if="book.is_available"
                                class="btn btn-primary"
                                :disabled="loadingRequest"
                                @click="requestBook"
                            >
                                <span
                                    v-if="loadingRequest"
                                    class="loading loading-spinner loading-sm"
                                ></span>

                                <span v-else>
                                    Requisitar Livro
                                </span>
                            </button>

                            <div v-else class="flex flex-wrap gap-2">
                                <button class="btn btn-disabled">
                                    Livro indisponível
                                </button>

                                <button
                                    v-if="user.role === 'citizen' && !hasAlert"
                                    class="btn btn-warning"
                                    :disabled="loadingAlert"
                                    @click="createAvailabilityAlert"
                                >
                                    <span
                                        v-if="loadingAlert"
                                        class="loading loading-spinner loading-sm"
                                    ></span>

                                    <span v-else>
                                        Avisar-me quando disponível
                                    </span>
                                </button>

                                <button
                                    v-if="user.role === 'citizen' && hasAlert"
                                    class="btn btn-disabled"
                                >
                                    ✔ Vais ser avisado
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIVROS RELACIONADOS -->
            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-2xl font-bold">
                        Livros relacionados
                    </h2>

                    <p class="mt-1 text-sm opacity-70">
                        Sugestões calculadas automaticamente com base em palavras-chave comuns nas descrições dos livros.
                    </p>
                </div>

                <div
                    v-if="relatedBooks?.length"
                    class="grid gap-5 md:grid-cols-2 xl:grid-cols-4"
                >
                    <Link
                        v-for="related in relatedBooks"
                        :key="related.id"
                        :href="route('books.show', related.id)"
                        class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                    >
                        <figure class="bg-base-200">
                            <img
                                v-if="related.cover_image"
                                :src="related.cover_image.startsWith('http') ? related.cover_image : '/storage/' + related.cover_image"
                                class="h-48 w-full object-cover"
                            />

                            <div
                                v-else
                                class="flex h-48 w-full items-center justify-center text-sm opacity-50"
                            >
                                Sem capa
                            </div>
                        </figure>

                        <div class="card-body p-5">
                            <h3 class="line-clamp-2 font-bold">
                                {{ related.name }}
                            </h3>

                            <p class="text-xs opacity-60">
                                {{ related.publisher?.name ?? '-' }}
                            </p>

                            <div class="mt-2">
                                <div class="badge badge-primary badge-outline">
                                    {{ related.relation_score }} palavras comuns
                                </div>
                            </div>

                            <div
                                v-if="related.common_keywords?.length"
                                class="mt-3 flex flex-wrap gap-1"
                            >
                                <span
                                    v-for="keyword in related.common_keywords"
                                    :key="keyword"
                                    class="badge badge-sm badge-outline"
                                >
                                    {{ keyword }}
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <div
                    v-else
                    class="rounded-2xl bg-base-200 p-6 text-center text-sm opacity-70"
                >
                    Ainda não existem livros relacionados suficientes com descrição semelhante.
                </div>
            </div>

            <!-- REVIEWS -->
            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h2 class="text-2xl font-bold">
                    Reviews dos cidadãos
                </h2>

                <div v-if="book.reviews?.length" class="mt-5 space-y-4">
                    <div
                        v-for="review in book.reviews"
                        :key="review.id"
                        class="rounded-2xl border border-base-300 p-4"
                    >
                        <p class="font-semibold">
                            {{ review.user?.name }}
                        </p>

                        <p class="mt-2 whitespace-pre-line text-sm opacity-80">
                            {{ review.content }}
                        </p>
                    </div>
                </div>

                <p v-else class="mt-4 opacity-60">
                    Este livro ainda não tem reviews ativas.
                </p>
            </div>

            <!-- HISTÓRICO -->
            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h2 class="text-2xl font-bold">
                    Histórico de Requisições
                </h2>

                <div class="mt-5 overflow-x-auto">
                    <table class="table">
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
                            <tr
                                v-for="req in book.requests"
                                :key="req.id"
                            >
                                <td>{{ req.number }}</td>
                                <td>{{ req.user?.name }}</td>
                                <td>{{ formatDate(req.request_date) }}</td>
                                <td>{{ formatDate(req.expected_return_date) }}</td>
                                <td>{{ formatDate(req.actual_return_date) }}</td>

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
                            </tr>

                            <tr v-if="!book.requests?.length">
                                <td colspan="6" class="text-center opacity-60">
                                    Sem histórico de requisições
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>