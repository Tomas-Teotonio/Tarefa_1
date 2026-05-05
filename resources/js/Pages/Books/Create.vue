<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    publishers: Array,
    authors: Array
})

const form = useForm({
    isbn: '',
    name: '',
    publisher_id: '',
    price: '',
    cover_image: null,
    authors: []
})

const submit = () => {
    form.post(route('books.store'))
}
</script>

<template>
    <AppLayout title="Criar Livro">

        <div class="max-w-xl mx-auto bg-base-100 p-6 rounded-2xl shadow">

            <h1 class="text-xl font-bold mb-4">Novo Livro</h1>

            <form @submit.prevent="submit" class="space-y-4">

                <div>
                    <label class="label">ISBN</label>
                    <input v-model="form.isbn" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label">Nome</label>
                    <input v-model="form.name" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label">Editora</label>
                    <select v-model="form.publisher_id" class="select select-bordered w-full">
                        <option value="">Selecionar</option>
                        <option v-for="p in publishers" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="label">Autores</label>

                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto border p-3 rounded-lg">

                        <label
                            v-for="a in authors"
                            :key="a.id"
                            class="flex items-center gap-2 cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="a.id"
                                v-model="form.authors"
                                class="checkbox checkbox-primary"
                            />

                            <span>{{ a.name }}</span>
                        </label>

                    </div>
                </div>

                <div>
                    <label class="label">Preço</label>
                    <input v-model="form.price" type="number" step="0.01" class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label">Capa</label>
                    <input type="file" @change="e => form.cover_image = e.target.files[0]" />
                </div>

                <button class="btn btn-primary w-full" :disabled="form.processing">
                    Criar Livro
                </button>

            </form>

        </div>

    </AppLayout>
</template>