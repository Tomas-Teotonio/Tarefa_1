<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    books: Object,
    publishers: Array,
    authors: Array,
    filters: Object,
});

const form = reactive({
    search: props.filters.search ?? '',
    publisher_id: props.filters.publisher_id ?? '',
    author_id: props.filters.author_id ?? '',
    sort: props.filters.sort ?? 'name',
    direction: props.filters.direction ?? 'asc',
});

const submit = () => {
    router.get(route('books.index'), form, {
        preserveState: true,
        replace: true,
    });
};

const reset = () => {
    form.search = '';
    form.publisher_id = '';
    form.author_id = '';
    form.sort = 'name';
    form.direction = 'asc';
    submit();
};

const sortBy = (column) => {
    if (form.sort === column) {
        form.direction = form.direction === 'asc' ? 'desc' : 'asc';
    } else {
        form.sort = column;
        form.direction = 'asc';
    }

    submit();
};

const indicator = (column) => {
    if (form.sort !== column) return '↕';
    return form.direction === 'asc' ? '↑' : '↓';
};
</script>

<template>
    <AppLayout title="Livros">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <div class="badge badge-primary badge-outline">Livros</div>
                        <div class="badge badge-secondary badge-outline">Tabela</div>
                        <div class="badge badge-accent badge-outline">Pesquisa</div>
                    </div>

                    <h1 class="text-3xl font-bold">Livros</h1>
                    <p class="mt-2 text-sm opacity-70">
                        ISBN, nome, editora, autores, bibliografia, capa e preço.
                    </p>
                </div>
                <a
                    :href="route('books.export', {
                        search: form.search || undefined,
                        publisher_id: form.publisher_id || undefined,
                        author_id: form.author_id || undefined,
                        sort: form.sort || undefined,
                        direction: form.direction || undefined,
                    })"
                    class="btn btn-secondary"
                >
                    Exportar Excel
                </a>
            </div>
        </template>
        

        <div class="space-y-6">
            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <form class="grid gap-4 md:grid-cols-2 xl:grid-cols-5" @submit.prevent="submit">
                    <div class="xl:col-span-2">
                        <label class="label">
                            <span class="label-text font-medium">Pesquisar</span>
                        </label>
                        <input
                            v-model="form.search"
                            type="text"
                            class="input input-bordered w-full"
                            placeholder="ISBN, nome, autor ou editora"
                        >
                    </div>

                    <div>
                        <label class="label">
                            <span class="label-text font-medium">Editora</span>
                        </label>
                        <select v-model="form.publisher_id" class="select select-bordered w-full">
                            <option value="">Todas</option>
                            <option v-for="publisher in publishers" :key="publisher.id" :value="publisher.id">
                                {{ publisher.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="label">
                            <span class="label-text font-medium">Autor</span>
                        </label>
                        <select v-model="form.author_id" class="select select-bordered w-full">
                            <option value="">Todos</option>
                            <option v-for="author in authors" :key="author.id" :value="author.id">
                                {{ author.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-1">Filtrar</button>
                        <button type="button" class="btn btn-outline" @click="reset">Limpar</button>
                        
                    </div>
                    
                </form>

                <div class="mt-4 alert alert-info">
                    <span class="text-sm">
                        A bibliografia está cifrada na base de dados, por isso não entra na pesquisa SQL desta tabela.
                    </span>
                </div>
            </div>

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <button class="btn btn-ghost btn-xs" @click="sortBy('isbn')">
                                        ISBN {{ indicator('isbn') }}
                                    </button>
                                </th>
                                <th>
                                    <button class="btn btn-ghost btn-xs" @click="sortBy('name')">
                                        Nome {{ indicator('name') }}
                                    </button>
                                </th>
                                <th>Editora</th>
                                <th>Autores</th>
                                <th>
                                    <button class="btn btn-ghost btn-xs" @click="sortBy('price')">
                                        Preço {{ indicator('price') }}
                                    </button>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="book in books.data" :key="book.id">
                                <td class="font-medium">{{ book.isbn }}</td>
                                <td>{{ book.name }}</td>
                                <td>{{ book.publisher?.name ?? '—' }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="author in book.authors"
                                            :key="author.id"
                                            class="badge badge-outline"
                                        >
                                            {{ author.name }}
                                        </span>
                                    </div>
                                </td>
                                <td>{{ Number(book.price).toFixed(2) }} €</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-center" v-if="books.links?.length > 3">
                    <div class="join">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="(link, index) in books.links"
                            :key="index"
                            :href="link.url || undefined"
                            class="join-item btn btn-sm"
                            :class="{
                                'btn-primary': link.active,
                                'btn-disabled': !link.url,
                            }"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>