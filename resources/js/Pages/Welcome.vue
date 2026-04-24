<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
    stats: Object,
});

const showingMobileMenu = ref(false);
const isDarkTheme = ref(false);

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
    <div class="min-h-screen bg-base-200 text-base-content">
        <Head title="Biblioteca" />

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
                                <p class="text-sm font-semibold leading-none">Biblioteca</p>
                                <p class="mt-1 text-xs opacity-60">Projeto 1</p>
                            </div>
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

                        <div class="flex items-center gap-2">
                            <Link
                                v-if="$page.props.auth && $page.props.auth.user"
                                :href="route('dashboard')"
                                class="btn btn-primary btn-sm"
                            >
                                Dashboard
                            </Link>

                            <template v-else>
                                <Link
                                    v-if="canLogin"
                                    :href="route('login')"
                                    class="btn btn-outline btn-sm"
                                >
                                    Entrar
                                </Link>

                                <Link
                                    v-if="canRegister"
                                    :href="route('register')"
                                    class="btn btn-primary btn-sm"
                                >
                                    Registar
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showingMobileMenu" class="border-t border-base-300 bg-base-100 lg:hidden">
                <div class="mx-auto max-w-7xl space-y-4 px-4 py-4 sm:px-6">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-base-300 bg-base-200 p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">Livros</span>
                                <div class="badge badge-primary badge-outline">Módulo</div>
                            </div>
                            <p class="mt-2 text-sm opacity-70">ISBN, nome, editora, autores, preço e capa.</p>
                        </div>

                        <div class="rounded-2xl border border-base-300 bg-base-200 p-4">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">Autores</span>
                                <div class="badge badge-secondary badge-outline">Módulo</div>
                            </div>
                            <p class="mt-2 text-sm opacity-70">Nome, foto e ligação aos livros.</p>
                        </div>

                        <div class="rounded-2xl border border-base-300 bg-base-200 p-4 sm:col-span-2">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">Editoras</span>
                                <div class="badge badge-accent badge-outline">Módulo</div>
                            </div>
                            <p class="mt-2 text-sm opacity-70">Nome, logótipo e notas internas.</p>
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

                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-if="$page.props.auth && $page.props.auth.user"
                            :href="route('dashboard')"
                            class="btn btn-primary btn-sm"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="btn btn-outline btn-sm"
                            >
                                Entrar
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="btn btn-primary btn-sm"
                            >
                                Registar
                            </Link>
                        </template>
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
                                Módulos
                            </p>

                            <div class="mt-4 space-y-3">
                                <div class="rounded-2xl bg-base-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-semibold">Livros</h3>
                                        <div class="badge badge-primary badge-outline">{{ stats?.books ?? 0 }}</div>
                                    </div>
                                    <p class="mt-2 text-sm opacity-70">
                                        Grande catálogo, filtros e possível exportação Excel.
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-base-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-semibold">Autores</h3>
                                        <div class="badge badge-secondary badge-outline">{{ stats?.authors ?? 0 }}</div>
                                    </div>
                                    <p class="mt-2 text-sm opacity-70">
                                        Informações detalhadas de autores com e associação aos seus livros.
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-base-200 p-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-semibold">Editoras</h3>
                                        <div class="badge badge-accent badge-outline">{{ stats?.publishers ?? 0 }}</div>
                                    </div>
                                    <p class="mt-2 text-sm opacity-70">
                                        Editoras com informações, notas e ligação ao catálogo.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                <section class="min-w-0">
                    <header class="mb-6">
                        <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                                <div>
                                    <div class="mb-3 flex flex-wrap gap-2">
                                        <div class="badge badge-primary badge-outline">Biblioteca</div>
                                    </div>

                                    <h1 class="text-3xl font-bold">
                                        Plataforma bibliotecária
                                    </h1>

                                    <p class="mt-2 max-w-3xl text-sm leading-relaxed opacity-70">
                                        Esta plataforma bibliotecária tem várias informações sobre os livros, autores e editoras, bem como funcionalidades de gestão e segurança para os utilizadores.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        v-if="$page.props.auth && $page.props.auth.user"
                                        :href="route('dashboard')"
                                        class="btn btn-primary"
                                    >
                                        Ir para dashboard
                                    </Link>

                                    <template v-else>
                                        <Link
                                            v-if="canLogin"
                                            :href="route('login')"
                                            class="btn btn-outline"
                                        >
                                            Entrar
                                        </Link>

                                        <Link
                                            v-if="canRegister"
                                            :href="route('register')"
                                            class="btn btn-primary"
                                        >
                                            Criar conta
                                        </Link>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </header>

                    <main class="space-y-6">
                        <section class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
                            <div class="hero rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                                <div class="hero-content flex-col items-start p-8">
                                    <div class="flex items-center">
                                        <div class="status status-success"></div>
                                        <span class="text-sm font-medium">Sistema bibliotecário</span>
                                    </div>

                                    <h2 class="mt-4 text-4xl font-bold leading-tight">
                                        Um sistema bibliotecário completo e simples de usar.
                                    </h2>

                                    <p class="mt-4 max-w-2xl text-base leading-relaxed opacity-70">
                                        O sistema inclui várias informações sobre os livros, autores e editoras, bem como funcionalidades de gestão e segurança para os utilizadores.
                                    </p>

                                    <div class="mt-6 flex flex-wrap gap-3">
                                        <div class="btn btn-primary pointer-events-none">Livros</div>
                                        <div class="btn btn-secondary pointer-events-none">Autores</div>
                                        <div class="btn btn-accent pointer-events-none">Editoras</div>
                                    </div>
                                </div>
                            </div>

                            <div class="stats stats-vertical rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                                <div class="stat">
                                    <div class="stat-title">Livros</div>
                                    <div class="stat-value text-primary">{{ stats?.books ?? 0 }}</div>
                                    <div class="stat-desc">Registos atuais</div>
                                </div>

                                <div class="stat">
                                    <div class="stat-title">Autores</div>
                                    <div class="stat-value text-secondary">{{ stats?.authors ?? 0 }}</div>
                                    <div class="stat-desc">Autores disponíveis</div>
                                </div>

                                <div class="stat">
                                    <div class="stat-title">Editoras</div>
                                    <div class="stat-value text-accent">{{ stats?.publishers ?? 0 }}</div>
                                    <div class="stat-desc">Editoras registadas</div>
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
                                        Temos varios livros registados.
                                    </p>
                                </div>
                            </div>

                            <div class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                                <div class="card-body">
                                    <div class="flex items-center justify-between">
                                        <h3 class="card-title">Autores</h3>
                                    </div>
                                    <p class="text-sm opacity-70">
                                        Cada autor pode estar associado a vários livros.
                                    </p>
                                </div>
                            </div>

                            <div class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                                <div class="card-body">
                                    <div class="flex items-center justify-between">
                                        <h3 class="card-title">Editoras</h3>
                                    </div>
                                    <p class="text-sm opacity-70">
                                        Varias informações sobre as editoras, incluindo os seus livros.
                                    </p>
                                </div>
                            </div>

                            <div class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                                <div class="card-body">
                                    <div class="flex items-center justify-between">
                                        <h3 class="card-title">2FA</h3>
                                    </div>
                                    <p class="text-sm opacity-70">
                                        Para melhor segurança das contas temos 2FA disponível.
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section class="grid gap-6 xl:grid-cols-[1.2fr_1fr]">
                            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-xl font-bold">Organização dos módulos</h3>
                                        <p class="mt-1 text-sm opacity-70">
                                            Estrutura clara para navegação e gestão de dados.
                                        </p>
                                    </div>

                                    <div class="badge badge-outline">Preview</div>
                                </div>

                                <div class="mt-6 grid gap-4 md:grid-cols-3">
                                    <div class="rounded-2xl bg-base-200 p-4">
                                        <h4 class="font-semibold">Livros</h4>
                                        <ul class="mt-3 space-y-2 text-sm opacity-80">
                                            <li>ISBN</li>
                                            <li>Nome</li>
                                            <li>Editora</li>
                                            <li>Autores</li>
                                            <li>Bibliografia</li>
                                            <li>Capa</li>
                                            <li>Preço</li>
                                        </ul>
                                    </div>

                                    <div class="rounded-2xl bg-base-200 p-4">
                                        <h4 class="font-semibold">Autores</h4>
                                        <ul class="mt-3 space-y-2 text-sm opacity-80">
                                            <li>Nome</li>
                                            <li>Foto</li>
                                        </ul>
                                    </div>

                                    <div class="rounded-2xl bg-base-200 p-4">
                                        <h4 class="font-semibold">Editoras</h4>
                                        <ul class="mt-3 space-y-2 text-sm opacity-80">
                                            <li>Nome</li>
                                            <li>Logótipo</li>
                                            <li>Notas</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                                <h3 class="text-xl font-bold">Experiência</h3>

                                <div class="mt-5 space-y-4">
                                    <div class="alert">
                                        <span class="text-sm">
                                            Nesta plataforma tens uma experiência simples.
                                        </span>
                                    </div>

                                    <div class="alert alert-success">
                                        <span class="text-sm">
                                            Nesta plataforma temos uma segurança 2FA robusta para proteger as contas dos utilizadores.
                                        </span>
                                    </div>

                                    <div class="alert alert-info">
                                        <span class="text-sm">
                                            Nesta plataforma a navegação é intuitiva, com uma estrutura clara e consistente entre os módulos.
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </section>
                    </main>
                </section>
            </div>
        </div>
    </div>
</template>