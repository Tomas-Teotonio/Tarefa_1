<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    book: Object,
    publishers: Array,
    authors: Array
})

const form = useForm({
    isbn: props.book.isbn,
    name: props.book.name,
    publisher_id: props.book.publisher_id,
    price: props.book.price,
    cover_image: null,
    authors: props.book.authors.map(a => a.id)
})

const submit = () => {
    form.put(route('books.update', props.book.id))
}
</script>

<template>
    <AppLayout title="Editar Livro">

        <div class="max-w-xl mx-auto bg-base-100 p-6 rounded-2xl shadow">

            <h1 class="text-xl font-bold mb-4">Editar Livro</h1>

            <form @submit.prevent="submit" class="space-y-4">

                <input v-model="form.isbn" class="input input-bordered w-full" />
                <input v-model="form.name" class="input input-bordered w-full" />

                <select v-model="form.publisher_id" class="select select-bordered w-full">
                    <option v-for="p in publishers" :key="p.id" :value="p.id">
                        {{ p.name }}
                    </option>
                </select>

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

                <input v-model="form.price" type="number" class="input input-bordered w-full" />

                <div v-if="book.cover_image">
                    <label class="label">Imagem atual</label>

                    <img
                        :src="'/storage/' + book.cover_image"
                        class="w-24 h-32 object-cover rounded mb-2"
                    />
                </div>

                <input
                    type="file"
                    @change="e => form.cover_image = e.target.files[0]"
                    class="file-input file-input-bordered w-full"
                />
                
                <button class="btn btn-primary w-full">
                    Guardar
                </button>

            </form>

        </div>

    </AppLayout>
</template>