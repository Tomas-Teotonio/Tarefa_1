<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    books: Array
})

const deleteBook = (id) => {
    if (confirm('Tens a certeza?')) {
        router.delete(route('books.destroy', id))
    }
}
</script>

<template>
    <AppLayout title="Gestor de Livros">

        <div class="max-w-6xl mx-auto bg-base-100 p-6 rounded-2xl shadow">

            <div class="flex justify-between mb-6">
                <h1 class="text-2xl font-bold">Gestor</h1>

                <a :href="route('admin.books.create')" class="btn btn-primary">
                    Criar Livro
                </a>
            </div>

            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Editora</th>
                        <th>Autores</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="book in books" :key="book.id">

                        <td>{{ book.name }}</td>
                        <td>{{ book.publisher?.name }}</td>

                        <td>
                            {{ book.authors.map(a => a.name).join(', ') }}
                        </td>

                        <td class="space-x-2">

                            <a
                                :href="route('admin.books.edit', book.id)"
                                class="btn btn-sm btn-warning"
                            >
                                Editar
                            </a>

                            <button
                                @click="deleteBook(book.id)"
                                class="btn btn-sm btn-error"
                            >
                                Apagar
                            </button>

                        </td>

                    </tr>
                </tbody>
            </table>

        </div>

    </AppLayout>
</template>