<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
    rooms: Array,
    directUsers: Array,
})

const page = usePage()
const authUser = page.props.auth.user

const avatarUrl = (item) => {
    if (!item) return null

    if (item.avatar) {
        return item.avatar.startsWith('http')
            ? item.avatar
            : '/storage/' + item.avatar
    }

    if (item.profile_photo_url) {
        return item.profile_photo_url
    }

    if (item.profile_photo_path) {
        return '/storage/' + item.profile_photo_path
    }

    return null
}

const initials = (name) => {
    if (!name) return '?'

    return name
        .split(' ')
        .map(part => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()
}
</script>

<template>
    <AppLayout title="Chat">
        <div class="space-y-6">

            <div class="rounded-[2rem] border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="badge badge-primary badge-outline mb-3">
                            Sistema de Chat
                        </div>

                        <h1 class="text-3xl font-bold">
                            Chat da equipa
                        </h1>

                        <p class="mt-2 text-sm opacity-70">
                            Conversas diretas e salas de chat com utilizadores convidados.
                        </p>
                    </div>

                    <Link
                        v-if="authUser.role === 'admin'"
                        :href="route('chat.rooms.create')"
                        class="btn btn-primary"
                    >
                        Criar sala
                    </Link>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">

                <!-- SALAS -->
                <div class="rounded-[2rem] border border-base-300 bg-base-100 p-6 shadow-sm">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold">
                                Salas
                            </h2>

                            <p class="mt-1 text-sm opacity-70">
                                Conversas em grupo com membros convidados.
                            </p>
                        </div>

                        <div class="badge badge-outline">
                            {{ rooms.length }}
                        </div>
                    </div>

                    <div v-if="rooms.length" class="grid gap-4 md:grid-cols-2">
                        <Link
                            v-for="room in rooms"
                            :key="room.id"
                            :href="route('chat.room', room.id)"
                            class="group rounded-[1.5rem] border border-base-300 bg-base-200 p-5 transition hover:-translate-y-1 hover:bg-base-300 hover:shadow-md"
                        >
                            <div class="flex items-center gap-4">
                                <div class="avatar">
                                    <div class="h-14 w-14 rounded-2xl bg-primary/20">
                                        <img
                                            v-if="avatarUrl(room)"
                                            :src="avatarUrl(room)"
                                            class="object-cover"
                                        />

                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center text-lg font-bold text-primary"
                                        >
                                            {{ initials(room.name) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="min-w-0">
                                    <h3 class="truncate font-bold">
                                        {{ room.name }}
                                    </h3>

                                    <p class="truncate text-xs opacity-60">
                                        #{{ room.reference }}
                                    </p>

                                    <p class="mt-1 text-xs opacity-60">
                                        {{ room.users?.length ?? 0 }} utilizadores
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-1">
                                <span
                                    v-for="user in room.users?.slice(0, 4)"
                                    :key="user.id"
                                    class="badge badge-sm badge-outline"
                                >
                                    {{ user.name }}
                                </span>

                                <span
                                    v-if="room.users?.length > 4"
                                    class="badge badge-sm"
                                >
                                    +{{ room.users.length - 4 }}
                                </span>
                            </div>
                        </Link>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl bg-base-200 p-8 text-center opacity-70"
                    >
                        Ainda não existem salas disponíveis.
                    </div>
                </div>

                <!-- DIRETAS -->
                <div class="rounded-[2rem] border border-base-300 bg-base-100 p-6 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-2xl font-bold">
                            Mensagens diretas
                        </h2>

                        <p class="mt-1 text-sm opacity-70">
                            Conversa direta com um membro da equipa.
                        </p>
                    </div>

                    <div v-if="directUsers.length" class="space-y-3">
                        <Link
                            v-for="user in directUsers"
                            :key="user.id"
                            :href="route('chat.direct', user.id)"
                            class="flex items-center gap-3 rounded-2xl bg-base-200 p-3 transition hover:bg-base-300"
                        >
                            <div class="avatar">
                                <div class="h-11 w-11 rounded-2xl bg-secondary/20">
                                    <img
                                        v-if="avatarUrl(user)"
                                        :src="avatarUrl(user)"
                                        class="object-cover"
                                    />

                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center text-sm font-bold text-secondary"
                                    >
                                        {{ initials(user.name) }}
                                    </div>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold">
                                    {{ user.name }}
                                </p>

                                <p class="truncate text-xs opacity-60">
                                    {{ user.email }}
                                </p>
                            </div>

                            <div
                                class="badge badge-sm"
                                :class="user.role === 'admin' ? 'badge-primary' : 'badge-outline'"
                            >
                                {{ user.role === 'admin' ? 'Admin' : 'User' }}
                            </div>
                        </Link>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl bg-base-200 p-8 text-center opacity-70"
                    >
                        Não existem utilizadores disponíveis.
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>