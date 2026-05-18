<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, watch } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const query = ref('')
const results = ref([])
const loading = ref(false)

const page = ref(1)
const totalItems = ref(0)
const hasNextPage = ref(false)
const hasPreviousPage = ref(false)
const searched = ref(false)

const toast = ref(null)

const minSearchLength = 3

let debounceTimer = null

const showToast = (message, type = 'success') => {
    toast.value = { message, type }

    setTimeout(() => {
        toast.value = null
    }, 4000)
}

const resetResults = () => {
    results.value = []
    totalItems.value = 0
    page.value = 1
    hasNextPage.value = false
    hasPreviousPage.value = false
}

const search = async (newPage = 1) => {
    const term = query.value.trim()

    if (term.length < minSearchLength) {
        searched.value = false
        resetResults()
        return
    }

    loading.value = true
    searched.value = true

    try {
        const response = await axios.get('/api/google-books/search', {
            params: {
                q: term,
                page: newPage,
            },
            headers: {
                Accept: 'application/json',
            }
        })

        const payload = response.data

        if (payload.error) {
            showToast(payload.error, 'error')
        }

        results.value = payload.items ?? []
        totalItems.value = payload.totalItems ?? 0
        page.value = payload.page ?? newPage
        hasNextPage.value = payload.hasNextPage ?? false
        hasPreviousPage.value = payload.hasPreviousPage ?? false

    } catch (error) {
        console.error(error)
        showToast('Erro ao pesquisar livros na Google Books API.', 'error')
        resetResults()
    } finally {
        loading.value = false
    }
}

watch(query, () => {
    clearTimeout(debounceTimer)

    const term = query.value.trim()

    if (term.length < minSearchLength) {
        searched.value = false
        resetResults()
        return
    }

    debounceTimer = setTimeout(() => {
        search(1)
    }, 2000)
})

const nextPage = () => {
    if (!hasNextPage.value) return
    search(page.value + 1)
}

const previousPage = () => {
    if (!hasPreviousPage.value) return
    search(page.value - 1)
}

const importBook = (book) => {
    router.post('/google-books/import', book, {
        preserveScroll: true,

        onSuccess: () => {
            book.exists = true
            showToast('Livro importado com sucesso!', 'success')
        },

        onError: () => {
            showToast('Erro ao importar livro.', 'error')
        }
    })
}
</script>

<template>
    <AppLayout title="Google Books">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="mb-4">
                    <div class="badge badge-primary badge-outline mb-3">
                        Google Books API
                    </div>

                    <h1 class="text-3xl font-bold">
                        Pesquisar livros externos
                    </h1>

                    <p class="mt-2 text-sm opacity-70">
                        Aqui podes pesquisar livros na Google Books API e importá-los para a base de dados.
                    </p>
                </div>

                <div class="flex flex-col gap-3 md:flex-row">
                    <input
                        v-model="query"
                        type="text"
                        placeholder="Ex: Harry Potter"
                        class="input input-bordered w-full"
                    />

                    <button
                        class="btn btn-primary"
                        :disabled="loading || query.trim().length < minSearchLength"
                        @click="search(1)"
                    >
                        <span v-if="loading" class="loading loading-spinner loading-sm"></span>
                        <span v-else>Pesquisar</span>
                    </button>
                </div>

                <p class="mt-3 text-xs opacity-60">
                    Escreve pelo menos 3 caracteres. A pesquisa é automática, mas com pausa para evitar demasiados pedidos à API.
                </p>
            </div>

            <!-- TOAST GLASS -->
            <div
                v-if="toast"
                class="rounded-3xl border border-base-300 bg-base-100/60 p-4 shadow-xl backdrop-blur-xl"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="badge"
                        :class="toast.type === 'success' ? 'badge-success' : 'badge-error'"
                    >
                        {{ toast.type === 'success' ? 'Sucesso' : 'Aviso' }}
                    </div>

                    <p class="text-sm">
                        {{ toast.message }}
                    </p>
                </div>
            </div>

            <div
                v-if="loading"
                class="rounded-3xl border border-base-300 bg-base-100 p-10 text-center shadow-sm"
            >
                <span class="loading loading-spinner loading-lg"></span>
                <p class="mt-3 opacity-70">A procurar livros...</p>
            </div>

            <div
                v-if="!loading && searched && query.trim().length >= minSearchLength && results.length === 0"
                class="rounded-3xl border border-base-300 bg-base-100 p-10 text-center shadow-sm"
            >
                <p class="opacity-60">
                    Nenhum resultado encontrado.
                </p>
            </div>

            <div
                v-if="!loading && query.trim().length > 0 && query.trim().length < minSearchLength"
                class="rounded-3xl border border-base-300 bg-base-100 p-10 text-center shadow-sm"
            >
                <p class="opacity-60">
                    Escreve pelo menos 3 caracteres para procurar livros.
                </p>
            </div>

            <div
                v-if="!loading && !query.trim()"
                class="rounded-3xl border border-base-300 bg-base-100 p-10 text-center shadow-sm"
            >
                <p class="opacity-60">
                    Escreve um título, autor ou ISBN para procurar livros.
                </p>
            </div>

            <div
                v-if="results.length"
                class="flex flex-col gap-3 rounded-3xl border border-base-300 bg-base-100 p-4 shadow-sm md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <p class="font-medium">
                        Resultados encontrados
                    </p>

                    <p class="text-sm opacity-60">
                        Página {{ page }} · {{ totalItems }} resultados estimados pela Google Books API
                    </p>
                </div>

                <div class="join">
                    <button
                        class="btn join-item"
                        :disabled="!hasPreviousPage || loading"
                        @click="previousPage"
                    >
                        Anterior
                    </button>

                    <button
                        class="btn join-item"
                        :disabled="!hasNextPage || loading"
                        @click="nextPage"
                    >
                        Próxima
                    </button>
                </div>
            </div>

            <div
                v-if="results.length"
                class="grid gap-6 md:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="book in results"
                    :key="book.google_id"
                    class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm"
                >
                    <figure class="bg-base-200">
                        <img
                            v-if="book.thumbnail"
                            :src="book.thumbnail"
                            class="h-72 w-full object-cover"
                        />

                        <div
                            v-else
                            class="flex h-72 w-full items-center justify-center text-sm opacity-50"
                        >
                            Sem capa
                        </div>
                    </figure>

                    <div class="card-body">
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-if="book.exists"
                                class="badge badge-success badge-outline"
                            >
                                Já existe
                            </div>

                            <div
                                v-if="book.isbn"
                                class="badge badge-outline"
                            >
                                ISBN {{ book.isbn }}
                            </div>

                            <div
                                v-else
                                class="badge badge-warning badge-outline"
                            >
                                Sem ISBN
                            </div>
                        </div>

                        <h2 class="card-title mt-2 line-clamp-2">
                            {{ book.title }}
                        </h2>

                        <p class="text-sm opacity-70">
                            {{ book.authors?.length ? book.authors.join(', ') : 'Autor desconhecido' }}
                        </p>

                        <p class="text-xs opacity-60">
                            {{ book.publisher }}
                        </p>

                        <p
                            v-if="book.published_date"
                            class="text-xs opacity-50"
                        >
                            Publicado em {{ book.published_date }}
                        </p>

                        <p
                            v-if="book.description"
                            class="mt-3 line-clamp-4 text-sm opacity-80"
                        >
                            {{ book.description }}
                        </p>

                        <div class="card-actions mt-4">
                            <button
                                v-if="book.exists"
                                class="btn btn-disabled w-full"
                            >
                                ✔ Já existe
                            </button>

                            <button
                                v-else-if="!book.isbn"
                                class="btn btn-disabled w-full"
                            >
                                Sem ISBN para importar
                            </button>

                            <button
                                v-else
                                class="btn btn-success w-full"
                                @click="importBook(book)"
                            >
                                Importar Livro
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>