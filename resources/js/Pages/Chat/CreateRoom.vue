<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
    users: Array,
})

const page = usePage()
const authUser = page.props.auth.user

const form = useForm({
    name: '',
    reference: '',
    avatar: null,
    users: [],
})

const toggleUser = (userId) => {
    if (form.users.includes(userId)) {
        form.users = form.users.filter(id => id !== userId)
    } else {
        form.users.push(userId)
    }
}

const avatarUrl = (user) => {
    if (user.profile_photo_url) {
        return user.profile_photo_url
    }

    if (user.profile_photo_path) {
        return '/storage/' + user.profile_photo_path
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

const submit = () => {
    form.post(route('chat.rooms.store'), {
        forceFormData: true,
    })
}
</script>

<template>
    <AppLayout title="Criar Sala">
        <div class="space-y-6">

            <div class="rounded-[2rem] border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="badge badge-primary badge-outline mb-3">
                            Admin
                        </div>

                        <h1 class="text-3xl font-bold">
                            Criar sala de chat
                        </h1>

                        <p class="mt-2 text-sm opacity-70">
                            Define a sala, referência e os utilizadores convidados.
                        </p>
                    </div>

                    <Link
                        :href="route('chat.index')"
                        class="btn btn-outline"
                    >
                        Voltar
                    </Link>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px]">

                <div class="rounded-[2rem] border border-base-300 bg-base-100 p-6 shadow-sm">
                    <form class="space-y-5" @submit.prevent="submit">

                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Nome da sala</span>
                            </label>

                            <input
                                v-model="form.name"
                                type="text"
                                class="input input-bordered w-full"
                                placeholder="Ex: Equipa de Biblioteca"
                            />

                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-sm text-error"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Referência</span>
                            </label>

                            <input
                                v-model="form.reference"
                                type="text"
                                class="input input-bordered w-full"
                                placeholder="Ex: equipa-biblioteca"
                            />

                            <p class="mt-1 text-xs opacity-60">
                                Opcional. Se ficares em branco, será gerada automaticamente.
                            </p>

                            <p
                                v-if="form.errors.reference"
                                class="mt-1 text-sm text-error"
                            >
                                {{ form.errors.reference }}
                            </p>
                        </div>

                        <div>
                            <label class="label">
                                <span class="label-text font-medium">Avatar da sala</span>
                            </label>

                            <input
                                type="file"
                                class="file-input file-input-bordered w-full"
                                @change="form.avatar = $event.target.files[0]"
                            />

                            <p
                                v-if="form.errors.avatar"
                                class="mt-1 text-sm text-error"
                            >
                                {{ form.errors.avatar }}
                            </p>
                        </div>

                        <div class="pt-3">
                            <button
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                Criar sala
                            </button>
                        </div>

                    </form>
                </div>

                <div class="rounded-[2rem] border border-base-300 bg-base-100 p-6 shadow-sm">
                    <h2 class="text-xl font-bold">
                        Utilizadores
                    </h2>

                    <p class="mt-1 text-sm opacity-70">
                        Seleciona os utilizadores que terão acesso à sala.
                    </p>

                    <p
                        v-if="form.errors.users"
                        class="mt-2 text-sm text-error"
                    >
                        {{ form.errors.users }}
                    </p>

                    <div class="mt-5 max-h-[520px] space-y-3 overflow-y-auto pr-1">
                        <button
                            v-for="user in users"
                            :key="user.id"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-2xl border p-3 text-left transition hover:bg-base-200"
                            :class="form.users.includes(user.id)
                                ? 'border-primary bg-primary/10'
                                : 'border-base-300 bg-base-100'"
                            @click="toggleUser(user.id)"
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
                                    <span
                                        v-if="user.id === authUser.id"
                                        class="text-xs opacity-60"
                                    >
                                        (tu)
                                    </span>
                                </p>

                                <p class="truncate text-xs opacity-60">
                                    {{ user.email }}
                                </p>
                            </div>

                            <input
                                type="checkbox"
                                class="checkbox checkbox-primary"
                                :checked="form.users.includes(user.id)"
                                readonly
                            />
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </AppLayout>
</template>