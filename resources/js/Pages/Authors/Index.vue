<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    authors: Object,
    filters: Object,
});

const form = reactive({
    search: props.filters.search ?? '',
    photo_filter: props.filters.photo_filter ?? 'all',
    sort: props.filters.sort ?? 'name',
    direction: props.filters.direction ?? 'asc',
});

const submit = () => {
    router.get(route('authors.index'), form, {
        preserveState: true,
        replace: true,
    });
};

const reset = () => {
    form.search = '';
    form.photo_filter = 'all';
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
    <AppLayout title="Autores">
        <template #header>
            <div>
                <div class="mb-3 flex flex-wrap gap-2">
                    <div class="badge badge-primary badge-outline">Autores</div>
                    <div class="badge badge-secondary badge-outline">Tabela</div>
                    <div class="badge badge-accent badge-outline">Filtros</div>
                </div>

                <h1 class="text-3xl font-bold">Autores</h1>
                <p class="mt-2 text-sm opacity-70">
                    Nome, foto e número de livros associados.
                </p>
            </div>
        </template>

        <div class="space-y-6">
            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <form class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" @submit.prevent="submit">
                    <div class="xl:col-span-2">
                        <label class="label">
                            <span class="label-text font-medium">Pesquisar</span>
                        </label>
                        <input
                            v-model="form.search"
                            type="text"
                            class="input input-bordered w-full"
                            placeholder="Nome do autor"
                        >
                    </div>

                    <div>
                        <label class="label">
                            <span class="label-text font-medium">Foto</span>
                        </label>
                        <select v-model="form.photo_filter" class="select select-bordered w-full">
                            <option value="all">Todos</option>
                            <option value="with_photo">Com foto</option>
                            <option value="without_photo">Sem foto</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-1">Filtrar</button>
                        <button type="button" class="btn btn-outline" @click="reset">Limpar</button>
                    </div>
                </form>
            </div>

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <button class="btn btn-ghost btn-xs" @click="sortBy('name')">
                                        Nome {{ indicator('name') }}
                                    </button>
                                </th>
                                <th>Foto</th>
                                <th>Livros</th>
                                <th>
                                    <button class="btn btn-ghost btn-xs" @click="sortBy('created_at')">
                                        Criado em {{ indicator('created_at') }}
                                    </button>
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="author in authors.data" :key="author.id">
                                <td class="font-medium">{{ author.name }}</td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="author.photo ? 'badge-success' : 'badge-ghost'"
                                    >
                                        {{ author.photo ? 'Com foto' : 'Sem foto' }}
                                    </span>
                                </td>
                                <td>{{ author.books_count }}</td>
                                <td>{{ new Date(author.created_at).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-center" v-if="authors.links?.length > 3">
                    <div class="join">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="(link, index) in authors.links"
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