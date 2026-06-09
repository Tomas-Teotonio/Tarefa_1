<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { nextTick, onMounted, onUnmounted, ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    chatUser: Object,
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
            route('chat.direct.messages.index', props.chatUser.id)
        )

        localMessages.value = response.data.messages ?? []

        const newLastId = localMessages.value.at(-1)?.id

        if (oldLastId !== newLastId) {
            scrollToBottom()
        }
    } catch (e) {
        console.error('Erro ao atualizar mensagens diretas', e)
    }
}

const submit = async () => {
    if (!body.value.trim()) return

    sending.value = true
    error.value = null

    try {
        const response = await axios.post(
            route('chat.direct.messages.store', props.chatUser.id),
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
    <AppLayout :title="`Chat com ${chatUser.name}`">
        <div class="mx-auto flex h-[calc(100vh-9rem)] max-w-5xl flex-col rounded-[2rem] border border-base-300 bg-base-100 shadow-sm">

            <div class="border-b border-base-300 p-5">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('chat.index')"
                            class="btn btn-outline btn-sm"
                        >
                            ←
                        </Link>

                        <div class="avatar">
                            <div class="h-14 w-14 rounded-2xl bg-secondary/20">
                                <img
                                    v-if="avatarUrl(chatUser)"
                                    :src="avatarUrl(chatUser)"
                                    class="object-cover"
                                />

                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-lg font-bold text-secondary"
                                >
                                    {{ initials(chatUser.name) }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <h1 class="text-2xl font-bold">
                                {{ chatUser.name }}
                            </h1>

                            <p class="text-sm opacity-60">
                                {{ chatUser.email }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="badge"
                        :class="chatUser.role === 'admin' ? 'badge-primary' : 'badge-outline'"
                    >
                        {{ chatUser.role === 'admin' ? 'Admin' : 'User' }}
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
                    Ainda não existem mensagens diretas.
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
                        placeholder="Escreve uma mensagem direta..."
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

        </div>
    </AppLayout>
</template>