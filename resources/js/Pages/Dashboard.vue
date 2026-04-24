<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';

defineProps({
    dashboardBooks: Array,
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <div class="badge badge-primary badge-outline">Biblioteca</div>
                        <div class="badge badge-secondary badge-outline">Dashboard</div>
                    </div>

                    <h1 class="text-3xl font-bold">
                        Painel principal da biblioteca
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-relaxed opacity-70">
                        Esta dashboard é o ponto de partida para a gestão da biblioteca, oferecendo uma visão geral dos módulos principais e acesso rápido às funcionalidades essenciais.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="/user/profile" class="btn btn-primary btn-sm">
                        Ir para perfil
                    </a>
                    <a href="/user/profile" class="btn btn-outline btn-sm">
                        Testar 2FA
                    </a>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
                <div class="hero rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="hero-content flex-col items-start p-8">
                        <div class="flex items-center">
                            <div class="status status-success"></div>
                            <span class="text-sm font-medium">Projeto preparado para evoluir</span>
                        </div>

                        <h2 class="mt-4 text-4xl font-bold leading-tight">
                            Projeto de gestão de biblioteca
                        </h2>

                        <p class="mt-4 max-w-2xl text-base leading-relaxed opacity-70">
                            O projeto é uma plataforma de gestão de biblioteca, construída com Laravel e Vue.js, que oferece funcionalidades para gerir livros, autores e editoras, além de recursos de segurança e exportação de dados.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link :href="route('books.index')" class="btn btn-primary">
                                Módulo Livros
                            </Link>

                            <Link :href="route('authors.index')" class="btn btn-secondary">
                                Módulo Autores
                            </Link>

                            <Link :href="route('publishers.index')" class="btn btn-accent">
                                Módulo Editoras
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4">
                    <div class="stats stats-vertical rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                        <div class="stat">
                            <div class="stat-title">Módulos principais</div>
                            <div class="stat-value text-primary">3</div>
                            <div class="stat-desc">Livros, Autores, Editoras</div>
                        </div>

                        <div class="stat">
                            <div class="stat-title">Segurança</div>
                            <div class="stat-value text-secondary">2FA</div>
                            <div class="stat-desc">Login reforçado</div>
                        </div>

                        <div class="stat">
                            <div class="stat-title">Exportação</div>
                            <div class="stat-value text-accent">Excel</div>
                            <div class="stat-desc">Livros</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title">Livros</h3>
                        </div>

                        <p class="text-sm opacity-70">
                            ISBN, nome, editora, autores, bibliografia, imagem da capa e preço.
                        </p>

                        <ul class="mt-2 space-y-2 text-sm opacity-80">
                            <li>• Relação com Editora</li>
                            <li>• Relação com vários Autores</li>
                            <li>• Pesquisa e filtros</li>
                        </ul>
                    </div>
                </div>

                <div class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title">Autores</h3>
                        </div>

                        <p class="text-sm opacity-70">
                            Autores e ligação aos livros publicados.
                        </p>

                        <ul class="mt-2 space-y-2 text-sm opacity-80">
                            <li>• Nome</li>
                            <li>• Foto</li>
                            <li>• Ligação a vários livros</li>
                        </ul>
                    </div>
                </div>

                <div class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title">Editoras</h3>
                        </div>

                        <p class="text-sm opacity-70">
                            Registo das editoras e notas internas.
                        </p>

                        <ul class="mt-2 space-y-2 text-sm opacity-80">
                            <li>• Nome</li>
                            <li>• Logótipo</li>
                            <li>• Notas</li>
                        </ul>
                    </div>
                </div>

                <div class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title">Segurança & Dados</h3>
                        </div>

                        <p class="text-sm opacity-70">
                            Temos proteção dos dados e exportação dos livros.
                        </p>

                        <ul class="mt-2 space-y-2 text-sm opacity-80">
                            <li>• Dados cifrados</li>
                            <li>• Export Excel</li>
                            <li>• 2FA</li>
                        </ul>
                    </div>
                </div>
            </section>

            <section>
                <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-xl font-bold">Pré-visualização da tabela de Livros</h3>
                            <p class="mt-1 text-sm opacity-70">
                                Com dados vindos da base de dados.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Link :href="route('books.index')" class="btn btn-outline btn-sm">
                                Ver tabela completa
                            </Link>
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ISBN</th>
                                    <th>Nome</th>
                                    <th>Editora</th>
                                    <th>Autores</th>
                                    <th>Preço</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-if="!dashboardBooks || dashboardBooks.length === 0">
                                    <td colspan="5" class="text-center opacity-70">
                                        Ainda não existem livros na base de dados.
                                    </td>
                                </tr>

                                <tr v-for="book in dashboardBooks" :key="book.id">
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
                </div>
            </section>

        </div>
    </AppLayout>
</template>