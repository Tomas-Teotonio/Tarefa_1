<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ApplicationMark from '@/Components/ApplicationMark.vue'
import Banner from '@/Components/Banner.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'

defineProps({
    title: {
        type: String,
        default: 'AI Chat',
    },
})

const showingMobileMenu = ref(false)
const isDarkTheme = ref(false)

const logout = () => {
    router.post(route('logout'))
}

const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-theme', theme)
    localStorage.setItem('theme', theme)
    isDarkTheme.value = theme === 'synthwave'
}

const toggleTheme = () => {
    applyTheme(isDarkTheme.value ? 'light' : 'synthwave')
}

onMounted(() => {
    const savedTheme = localStorage.getItem('theme') || 'light'
    applyTheme(savedTheme)
})
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <div class="h-[100dvh] overflow-hidden bg-base-200 text-base-content">
            <nav class="relative z-50 border-b border-base-300 bg-base-100/90 backdrop-blur">
                <div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">
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
                                    <p class="mt-1 text-xs opacity-60">AI Chat via OpenRouter</p>
                                </div>
                            </Link>
                        </div>

                        <div class="hidden items-center gap-2 lg:flex">
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
                                :href="route('ai-chat.index')"
                                :class="route().current('ai-chat.*') ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-ghost'"
                            >
                                AI Chat
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
                            <div class="hidden items-center rounded-2xl bg-base-200 px-3 py-2 sm:flex">
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
                                        stroke-linejoin="round"
                                    >
                                        <circle cx="12" cy="12" r="5" />
                                        <path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4" />
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
                                        stroke-linejoin="round"
                                    >
                                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
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

                                        <span class="hidden text-sm font-medium md:block">
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
                    <div class="mx-auto max-w-7xl space-y-3 px-4 py-4 sm:px-6">
                        <Link :href="route('books.index')" class="btn btn-outline btn-sm w-full justify-start">
                            Livros
                        </Link>

                        <Link :href="route('chat.index')" class="btn btn-outline btn-sm w-full justify-start">
                            Chat
                        </Link>

                        <Link :href="route('ai-chat.index')" class="btn btn-primary btn-sm w-full justify-start">
                            AI Chat
                        </Link>

                        <Link
                            v-if="$page.props.auth.user.role === 'admin'"
                            :href="route('admin.logs.index')"
                            class="btn btn-outline btn-sm w-full justify-start"
                        >
                            Logs
                        </Link>

                        <div class="flex items-center justify-between rounded-2xl bg-base-200 px-4 py-3">
                            <span class="text-sm font-medium">Tema escuro</span>

                            <input
                                v-model="isDarkTheme"
                                type="checkbox"
                                value="synthwave"
                                class="toggle theme-controller"
                                @change="toggleTheme"
                            />
                        </div>
                    </div>
                </div>
            </nav>

            <div class="relative h-[calc(100dvh-4rem)] min-h-0 overflow-hidden">
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute left-1/4 top-20 h-72 w-72 rounded-full bg-primary/10 blur-3xl"></div>
                    <div class="absolute bottom-16 right-20 h-80 w-80 rounded-full bg-secondary/10 blur-3xl"></div>
                    <div class="absolute bottom-0 left-1/2 h-64 w-64 -translate-x-1/2 rounded-full bg-accent/10 blur-3xl"></div>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>