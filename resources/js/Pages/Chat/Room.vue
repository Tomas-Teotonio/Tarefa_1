<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { nextTick, onMounted, onUnmounted, ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    room: Object,
    messages: Array,
})

const page = usePage()
const authUser = page.props.auth.user

const messagesBox = ref(null)
const localMessages = ref([...props.messages])

const body = ref('')
const sending = ref(false)
const error = ref(null)

let pollingTimer = null

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

const formatTime = (date) => {
    if (!date) return ''

    return new Date(date).toLocaleTimeString('pt-PT', {
        hour: '2-digit',
        minute: '2-digit',
    })
}

const scrollToBottom = async () => {
    await nextTick()

    if (messagesBox.value) {
        messagesBox.value.scrollTop = messagesBox.value.scrollHeight
    }
}

const fetchMessages = async () => {
    try {
        const oldLastId = localMessages.value.at(-1)?.id

        const response = await axios.get(
            route('chat.room.messages.index', props.room.id)
        )

        localMessages.value = response.data.messages ?? []

        const newLastId = localMessages.value.at(-1)?.id

        if (oldLastId !== newLastId) {
            scrollToBottom()
        }
    } catch (e) {
        console.error('Erro ao atualizar mensagens da sala', e)
    }
}

const submit = async () => {
    if (!body.value.trim()) return

    sending.value = true
    error.value = null

    try {
        const response = await axios.post(
            route('chat.room.messages.store', props.room.id),
            {
                body: body.value,
            }
        )

        if (response.data.message) {
            localMessages.value.push(response.data.message)
        }

        body.value = ''
        scrollToBottom()
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Erro ao enviar mensagem.'
    } finally {
        sending.value = false
    }
}

onMounted(() => {
    scrollToBottom()

    pollingTimer = setInterval(() => {
        fetchMessages()
    }, 3000)
})

onUnmounted(() => {
    if (pollingTimer) {
        clearInterval(pollingTimer)
    }
})
</script>

<template>
    <AppLayout :title="room.name">
        <div class="grid h-[calc(100vh-9rem)] gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">

            <aside class="rounded-[2rem] border border-base-300 bg-base-100 p-5 shadow-sm">
                <Link
                    :href="route('chat.index')"
                    class="btn btn-outline btn-sm mb-5"
                >
                    ← Voltar ao chat
                </Link>

                <div class="flex items-center gap-4">
                    <div class="avatar">
                        <div class="h-16 w-16 rounded-2xl bg-primary/20">
                            <img
                                v-if="avatarUrl(room)"
                                :src="avatarUrl(room)"
                                class="object-cover"
                            />

                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-xl font-bold text-primary"
                            >
                                {{ initials(room.name) }}
                            </div>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <h1 class="truncate text-xl font-bold">
                            {{ room.name }}
                        </h1>

                        <p class="truncate text-xs opacity-60">
                            #{{ room.reference }}
                        </p>
                    </div>
                </div>

                <div class="divider"></div>

                <h2 class="font-bold">
                    Utilizadores
                </h2>

                <div class="mt-4 space-y-3">
                    <div
                        v-for="user in room.users"
                        :key="user.id"
                        class="flex items-center gap-3 rounded-2xl bg-base-200 p-3"
                    >
                        <div class="avatar">
                            <div class="h-10 w-10 rounded-xl bg-secondary/20">
                                <img
                                    v-if="avatarUrl(user)"
                                    :src="avatarUrl(user)"
                                    class="object-cover"
                                />

                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-xs font-bold text-secondary"
                                >
                                    {{ initials(user.name) }}
                                </div>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">
                                {{ user.name }}
                            </p>

                            <p class="truncate text-xs opacity-60">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="flex min-h-0 flex-col rounded-[2rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold">
                                {{ room.name }}
                            </h2>

                            <p class="text-sm opacity-60">
                                Conversa da sala · atualização automática
                            </p>
                        </div>

                        <div class="badge badge-primary badge-outline">
                            Sala
                        </div>
                    </div>
                </div>

                <div
                    ref="messagesBox"
                    class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-base-200/60 p-5"
                >
                    <div
                        v-for="message in localMessages"
                        :key="message.id"
                        class="chat"
                        :class="message.sender_id === authUser.id ? 'chat-end' : 'chat-start'"
                    >
                        <div class="chat-image avatar">
                            <div class="h-10 w-10 rounded-2xl bg-base-300">
                                <img
                                    v-if="avatarUrl(message.sender)"
                                    :src="avatarUrl(message.sender)"
                                    class="object-cover"
                                />

                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-xs font-bold"
                                >
                                    {{ initials(message.sender?.name) }}
                                </div>
                            </div>
                        </div>

                        <div class="chat-header text-xs opacity-70">
                            {{ message.sender?.name }}
                            <time class="ml-1">
                                {{ formatTime(message.created_at) }}
                            </time>
                        </div>

                        <div
                            class="chat-bubble whitespace-pre-line"
                            :class="message.sender_id === authUser.id ? 'chat-bubble-primary' : ''"
                        >
                            {{ message.body }}
                        </div>
                    </div>

                    <div
                        v-if="!localMessages.length"
                        class="flex h-full items-center justify-center text-center opacity-60"
                    >
                        Ainda não existem mensagens nesta sala.
                    </div>
                </div>

                <form
                    class="border-t border-base-300 bg-base-100 p-4"
                    @submit.prevent="submit"
                >
                    <div class="flex gap-3">
                        <textarea
                            v-model="body"
                            class="textarea textarea-bordered min-h-12 flex-1 resize-none"
                            placeholder="Escreve uma mensagem..."
                            @keydown.enter.exact.prevent="submit"
                        ></textarea>

                        <button
                            class="btn btn-primary"
                            :disabled="sending || !body.trim()"
                        >
                            <span
                                v-if="sending"
                                class="loading loading-spinner loading-sm"
                            ></span>

                            <span v-else>
                                Enviar
                            </span>
                        </button>
                    </div>

                    <p
                        v-if="error"
                        class="mt-2 text-sm text-error"
                    >
                        {{ error }}
                    </p>
                </form>
            </section>

        </div>
    </AppLayout>
</template>