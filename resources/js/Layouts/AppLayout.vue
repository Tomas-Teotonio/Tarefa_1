<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    title: String,
});

const showingMobileMenu = ref(false);
const isDarkTheme = ref(false);

const logout = () => {
    router.post(route('logout'));
};

const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    isDarkTheme.value = theme === 'synthwave';
};

const toggleTheme = () => {
    applyTheme(isDarkTheme.value ? 'synthwave' : 'light');
};

onMounted(() => {
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);
});
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <div class="min-h-screen bg-base-200 text-base-content">
            <nav class="sticky top-0 z-50 border-b border-base-300 bg-base-100/90 backdrop-blur">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <button
                                class="btn btn-ghost btn-square btn-sm lg:hidden"
                                @click="showingMobileMenu = !showingMobileMenu"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        v-if="!showingMobileMenu"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        v-else
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>

                            <Link :href="route('dashboard')" class="flex items-center gap-3">
                                <div class="rounded-2xl bg-primary/10 p-2">
                                    <ApplicationMark class="block h-9 w-auto" />
                                </div>

                                <div class="hidden sm:block">
                                    <p class="text-sm font-semibold leading-none">Biblioteca Admin</p>
                                    <p class="mt-1 text-xs opacity-60">Projeto 1</p>
                                </div>
                            </Link>
                        </div>

                        <div class="hidden lg:flex items-center gap-2">
                            <Link
                                :href="route('books.index')"
                                :class="route().current('books.*') ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-ghost'"
                            >
                                Livros
                            </Link>

                            <Link
                                :href="route('authors.index')"
                                :class="route().current('authors.*') ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-ghost'"
                            >
                                Autores
                            </Link>

                            <Link
                                :href="route('publishers.index')"
                                :class="route().current('publishers.*') ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-ghost'"
                            >
                                Editoras
                            </Link>

                            <Link
                                :href="route('chat.index')"
                                :class="route().current('chat.*') ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-ghost'"
                            >
                                Chat
                            </Link>

                            <Link
                                v-if="$page.props.auth.user.role === 'admin'"
                                :href="route('admin.logs.index')"
                                :class="route().current('admin.logs.*') ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-ghost'"
                            >
                                Logs
                            </Link>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="hidden sm:flex items-center rounded-2xl bg-base-200 px-3 py-2">
                                <label class="flex cursor-pointer gap-2">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="5" />
                                    <path
                                    d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4" />
                                </svg>

                                <input
                                    v-model="isDarkTheme"
                                    type="checkbox"
                                    value="synthwave"
                                    class="toggle theme-controller"
                                    @change="toggleTheme"
                                />

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                                </svg>
                                </label>
                            </div>

                            <Dropdown align="right" width="56">
                                <template #trigger>
                                    <button
                                        v-if="$page.props.jetstream.managesProfilePhotos"
                                        class="flex items-center gap-3 rounded-full border border-transparent p-1 transition hover:bg-base-200"
                                    >
                                        <img
                                            class="h-9 w-9 rounded-full object-cover"
                                            :src="$page.props.auth.user.profile_photo_url"
                                            :alt="$page.props.auth.user.name"
                                        >
                                        <span class="hidden md:block text-sm font-medium">
                                            {{ $page.props.auth.user.name }}
                                        </span>
                                    </button>

                                    <button
                                        v-else
                                        type="button"
                                        class="btn btn-ghost btn-sm gap-2 normal-case"
                                    >
                                        {{ $page.props.auth.user.name }}
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="block px-4 py-2 text-xs opacity-60">
                                        Conta
                                    </div>

                                    <DropdownLink :href="route('profile.show')">
                                        Perfil
                                    </DropdownLink>

                                    <div class="border-t border-base-300" />

                                    <form @submit.prevent="logout">
                                        <DropdownLink as="button">
                                            Log Out
                                        </DropdownLink>
                                    </form>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>

                <div v-if="showingMobileMenu" class="border-t border-base-300 bg-base-100 lg:hidden">
                    <div class="mx-auto max-w-7xl space-y-4 px-4 py-4 sm:px-6">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-base-300 bg-base-200 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold">Livros</span>
                                    <div class="badge badge-primary badge-outline">Em breve</div>
                                </div>
                                <p class="mt-2 text-sm opacity-70">ISBN, nome, editora, autores, preço, capa.</p>
                            </div>

                            <div class="rounded-2xl border border-base-300 bg-base-200 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold">Autores</span>
                                    <div class="badge badge-secondary badge-outline">Em breve</div>
                                </div>
                                <p class="mt-2 text-sm opacity-70">Nome e foto dos autores.</p>
                            </div>

                            <div class="rounded-2xl border border-base-300 bg-base-200 p-4 sm:col-span-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold">Editoras</span>
                                    <div class="badge badge-accent badge-outline">Em breve</div>
                                </div>
                                <p class="mt-2 text-sm opacity-70">Nome, logótipo e notas.</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-2xl bg-base-200 px-4 py-3">
                            <span class="text-sm font-medium">Tema escuro</span>

                            <label class="flex cursor-pointer gap-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <circle cx="12" cy="12" r="5" />
                                <path
                                d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4" />
                            </svg>

                            <input
                                v-model="isDarkTheme"
                                type="checkbox"
                                value="synthwave"
                                class="toggle theme-controller"
                                @change="toggleTheme"
                            />

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                            </svg>
                            </label>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="grid gap-6 lg:grid-cols-[270px_minmax(0,1fr)]">
                    <aside class="hidden lg:block">
                        <div class="sticky top-24 space-y-4">
                            <div class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-wider opacity-60">
                                    Estrutura
                                </p>

                                <div class="mt-4 space-y-3">
                                    <Link :href="route('books.index')" class="block rounded-2xl bg-base-200 p-4 transition hover:bg-base-300">
                                        <div class="flex items-center justify-between">
                                            <h3 class="font-semibold">Livros</h3>
                                            <div class="badge badge-primary badge-outline">Tabela</div>
                                        </div>
                                        <p class="mt-2 text-sm opacity-70">
                                            Pesquisa, ordenação, filtros e exportação Excel.
                                        </p>
                                    </Link>

                                    <Link :href="route('authors.index')" class="block rounded-2xl bg-base-200 p-4 transition hover:bg-base-300">
                                        <div class="flex items-center justify-between">
                                            <h3 class="font-semibold">Autores</h3>
                                            <div class="badge badge-secondary badge-outline">Tabela</div>
                                        </div>
                                        <p class="mt-2 text-sm opacity-70">
                                            Nome, foto e ligação com vários livros.
                                        </p>
                                    </Link>

                                    <Link :href="route('publishers.index')" class="block rounded-2xl bg-base-200 p-4 transition hover:bg-base-300">
                                        <div class="flex items-center justify-between">
                                            <h3 class="font-semibold">Editoras</h3>
                                            <div class="badge badge-accent badge-outline">Tabela</div>
                                        </div>
                                        <p class="mt-2 text-sm opacity-70">
                                            Nome, logótipo e notas da editora.
                                        </p>
                                    </Link>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm">
                                <h3 class="font-semibold">Acesso rápido</h3>

                                <div class="mt-4 flex flex-col gap-2">
                                    <Link :href="route('dashboard')" class="btn btn-outline btn-sm justify-start">
                                        Dashboard
                                    </Link>

                                    <Link :href="route('profile.show')" class="btn btn-outline btn-sm justify-start">
                                        Perfil / 2FA
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <section class="min-w-0">
                        <header v-if="$slots.header" class="mb-6">
                            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                                <slot name="header" />
                            </div>
                        </header>

                        <main>
                            <slot />
                        </main>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>