<script setup>
import AiChatLayout from '@/Layouts/AiChatLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import MarkdownIt from 'markdown-it'

const props = defineProps({
    activeConversation: {
        type: Object,
        default: null,
    },
    conversations: {
        type: Array,
        default: () => [],
    },
    models: {
        type: Array,
        default: () => [],
    },
    openRouterConfigured: {
        type: Boolean,
        default: false,
    },
    modelsError: {
        type: String,
        default: null,
    },
})

const markdown = new MarkdownIt({
    html: false,
    linkify: true,
    breaks: true,
    typographer: true,
})

const renderMarkdown = (content) => {
    if (!content) return ''

    return markdown.render(content)
}

const conversationSearch = ref('')
const modelSearch = ref('')
const showSettingsMobile = ref(false)

const messagesBox = ref(null)
const bottomAnchor = ref(null)
const shouldAutoScroll = ref(true)

const streaming = ref(false)
const streamError = ref(null)
const formErrors = ref({})
const createdConversation = ref(null)

const copiedMessageId = ref(null)

const showDeleteModal = ref(false)
const deletingConversation = ref(false)

const preferredModel = computed(() => {
    return props.models.find(model => model.id === 'openrouter/free')
        ?? props.models.find(model => model.id?.includes(':free'))
        ?? props.models[0]
        ?? null
})

const form = reactive({
    message: '',
    model_id: props.activeConversation?.model_id || preferredModel.value?.id || '',
    temperature: Number(props.activeConversation?.temperature ?? 0.7),
    max_tokens: Number(props.activeConversation?.max_tokens ?? 1024),
})

const localMessages = ref(
    props.activeConversation?.messages
        ? [...props.activeConversation.messages]
        : []
)

let typingQueue = []
let typingTimer = null

const filteredConversations = computed(() => {
    const term = conversationSearch.value.trim().toLowerCase()

    if (!term) {
        return props.conversations
    }

    return props.conversations.filter(conversation => {
        return conversation.title?.toLowerCase().includes(term)
    })
})

const filteredModels = computed(() => {
    const term = modelSearch.value.trim().toLowerCase()

    return props.models
        .filter(model => {
            if (!term) return true

            return model.name?.toLowerCase().includes(term)
                || model.id?.toLowerCase().includes(term)
        })
        .slice(0, 120)
})

const activeMessages = computed(() => {
    return localMessages.value
})

const selectedModel = computed(() => {
    return props.models.find(model => model.id === form.model_id)
})

const canSubmit = computed(() => {
    return props.openRouterConfigured
        && form.model_id
        && form.message.trim()
        && !streaming.value
})

const formatDate = (date) => {
    if (!date) return ''

    return new Date(date).toLocaleDateString('pt-PT', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const formatTime = (date) => {
    if (!date) return ''

    return new Date(date).toLocaleTimeString('pt-PT', {
        hour: '2-digit',
        minute: '2-digit',
    })
}

const roleLabel = (role) => {
    if (role === 'user') return 'Tu'
    if (role === 'assistant') return 'IA'
    return role
}

const roleInitial = (role) => {
    if (role === 'user') return 'T'
    if (role === 'assistant') return 'AI'
    return '?'
}

const scrollToBottom = async (smooth = true) => {
    await nextTick()

    const element = messagesBox.value

    if (!element) {
        bottomAnchor.value?.scrollIntoView({
            behavior: smooth ? 'smooth' : 'auto',
            block: 'end',
        })

        return
    }

    element.scrollTo({
        top: element.scrollHeight,
        behavior: smooth ? 'smooth' : 'auto',
    })
}

const handleMessagesScroll = () => {
    const element = messagesBox.value

    if (!element) return

    const distanceFromBottom = element.scrollHeight - element.scrollTop - element.clientHeight

    shouldAutoScroll.value = distanceFromBottom < 260
}

const splitTextForTyping = (text) => {
    return text.match(/(\S+\s*|\s+)/g) ?? [text]
}

const startTypingAnimation = (assistantMessage) => {
    if (typingTimer) return

    typingTimer = window.setInterval(() => {
        if (!typingQueue.length) {
            window.clearInterval(typingTimer)
            typingTimer = null
            return
        }

        const nextPart = typingQueue.shift()

        assistantMessage.content += nextPart

        if (shouldAutoScroll.value) {
            scrollToBottom(false)
        }
    }, 45)
}

const queueAssistantText = (text, assistantMessage) => {
    if (!text) return

    typingQueue.push(...splitTextForTyping(text))

    startTypingAnimation(assistantMessage)
}

const waitForTypingToFinish = () => {
    return new Promise(resolve => {
        const check = () => {
            if (!typingTimer && typingQueue.length === 0) {
                resolve()
                return
            }

            window.setTimeout(check, 30)
        }

        check()
    })
}

const clearTypingAnimation = () => {
    typingQueue = []

    if (typingTimer) {
        window.clearInterval(typingTimer)
        typingTimer = null
    }
}

const csrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
}

const parseSseBlock = (block) => {
    const lines = block.split('\n')

    let event = 'message'
    const dataLines = []

    for (const line of lines) {
        if (line.startsWith('event:')) {
            event = line.slice(6).trim()
        }

        if (line.startsWith('data:')) {
            dataLines.push(line.slice(5).trim())
        }
    }

    if (!dataLines.length) {
        return null
    }

    try {
        return {
            event,
            data: JSON.parse(dataLines.join('\n')),
        }
    } catch {
        return null
    }
}

const handleStreamEvent = (payload, assistantMessage) => {
    if (!payload) return

    if (payload.event === 'conversation') {
        createdConversation.value = payload.data
    }

    if (payload.event === 'delta') {
        queueAssistantText(payload.data.content ?? '', assistantMessage)
    }

    if (payload.event === 'error') {
        streamError.value = payload.data.message ?? 'Erro ao receber resposta da IA.'
    }

    if (payload.event === 'done') {
        createdConversation.value = {
            id: payload.data.conversation_id,
            url: payload.data.url,
        }
    }
}

const copyMessage = async (message) => {
    if (!message?.content) return

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(message.content)
        } else {
            const textarea = document.createElement('textarea')

            textarea.value = message.content
            textarea.setAttribute('readonly', '')
            textarea.style.position = 'fixed'
            textarea.style.left = '-9999px'
            textarea.style.top = '-9999px'

            document.body.appendChild(textarea)

            textarea.select()
            document.execCommand('copy')

            document.body.removeChild(textarea)
        }

        copiedMessageId.value = message.id

        window.setTimeout(() => {
            if (copiedMessageId.value === message.id) {
                copiedMessageId.value = null
            }
        }, 1600)
    } catch {
        streamError.value = 'Não foi possível copiar a resposta.'
    }
}

const deleteConversation = () => {
    if (!props.activeConversation || streaming.value) return

    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (deletingConversation.value) return

    showDeleteModal.value = false
}

const confirmDeleteConversation = () => {
    if (!props.activeConversation || deletingConversation.value) return

    deletingConversation.value = true

    router.delete(route('ai-chat.destroy', props.activeConversation.id), {
        preserveScroll: false,
        onFinish: () => {
            deletingConversation.value = false
            showDeleteModal.value = false
        },
    })
}

const submit = async () => {
    if (!canSubmit.value) return

    const userText = form.message.trim()

    streaming.value = true
    streamError.value = null
    formErrors.value = {}
    shouldAutoScroll.value = true

    const now = new Date().toISOString()

    const userMessage = reactive({
        id: `temp-user-${Date.now()}`,
        role: 'user',
        content: userText,
        model_id: form.model_id,
        created_at: now,
    })

    const assistantMessage = reactive({
        id: `temp-assistant-${Date.now()}`,
        role: 'assistant',
        content: '',
        model_id: form.model_id,
        created_at: now,
        streaming: true,
    })

    localMessages.value.push(userMessage)
    localMessages.value.push(assistantMessage)

    form.message = ''

    await scrollToBottom(true)

    try {
        const response = await fetch(route('ai-chat.stream'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                conversation_id: props.activeConversation?.id ?? createdConversation.value?.id ?? null,
                message: userText,
                model_id: form.model_id,
                temperature: form.temperature,
                max_tokens: form.max_tokens,
            }),
        })

        if (!response.ok) {
            let errorMessage = 'Erro ao enviar mensagem.'

            try {
                const json = await response.json()

                if (json.errors) {
                    formErrors.value = json.errors
                }

                errorMessage = json.message ?? errorMessage
            } catch {
                //
            }

            throw new Error(errorMessage)
        }

        const reader = response.body.getReader()
        const decoder = new TextDecoder('utf-8')

        let buffer = ''

        while (true) {
            const { value, done } = await reader.read()

            if (done) break

            buffer += decoder.decode(value, { stream: true })

            let separatorIndex

            while ((separatorIndex = buffer.indexOf('\n\n')) !== -1) {
                const block = buffer.slice(0, separatorIndex)
                buffer = buffer.slice(separatorIndex + 2)

                const payload = parseSseBlock(block)
                handleStreamEvent(payload, assistantMessage)
            }
        }

        if (buffer.trim()) {
            const payload = parseSseBlock(buffer.trim())
            handleStreamEvent(payload, assistantMessage)
        }

        await waitForTypingToFinish()

        assistantMessage.streaming = false

        await scrollToBottom(true)

        if (!assistantMessage.content && streamError.value) {
            assistantMessage.content = 'Erro: ' + streamError.value
        }

        await scrollToBottom(true)

        if (!props.activeConversation && createdConversation.value?.url) {
            window.setTimeout(() => {
                router.visit(createdConversation.value.url, {
                    replace: true,
                    preserveScroll: false,
                })
            }, 300)
        }
    } catch (error) {
        clearTypingAnimation()

        assistantMessage.streaming = false
        assistantMessage.content = 'Erro: ' + error.message
        streamError.value = error.message

        await scrollToBottom(true)
    } finally {
        streaming.value = false
    }
}

const useSuggestion = (text) => {
    form.message = text

    nextTick(() => {
        scrollToBottom(true)
    })
}

const promptSuggestions = [
    {
        title: 'Sugestões de leitura',
        text: 'Sugere-me 5 livros interessantes para começar a ler este mês, com uma breve explicação de cada um.',
        icon: '📚',
    },
    {
        title: 'Descobrir géneros',
        text: 'Explica-me os principais géneros literários e ajuda-me a escolher um género com base nos meus gostos.',
        icon: '🔎',
    },
    {
        title: 'Plano de leitura',
        text: 'Cria um plano de leitura simples para eu ler mais livros ao longo das próximas 4 semanas.',
        icon: '🗓️',
    },
    {
        title: 'Resumo sem spoilers',
        text: 'Faz um resumo sem spoilers de um livro que eu indicar e diz-me para que tipo de leitor é recomendado.',
        icon: '✨',
    },
]

watch(
    () => props.activeConversation?.id,
    () => {
        form.model_id = props.activeConversation?.model_id || preferredModel.value?.id || ''
        form.temperature = Number(props.activeConversation?.temperature ?? 0.7)
        form.max_tokens = Number(props.activeConversation?.max_tokens ?? 1024)
        form.message = ''

        localMessages.value = props.activeConversation?.messages
            ? [...props.activeConversation.messages]
            : []

        createdConversation.value = null
        streamError.value = null
        formErrors.value = {}

        clearTypingAnimation()

        nextTick(() => {
            scrollToBottom(false)
        })
    }
)

onMounted(() => {
    scrollToBottom(false)
})

onBeforeUnmount(() => {
    clearTypingAnimation()
})
</script>

<template>
    <AiChatLayout title="AI Chat">
        <div class="relative z-10 grid h-full min-h-0 grid-cols-1 overflow-hidden lg:grid-cols-[300px_minmax(0,1fr)] xl:grid-cols-[300px_minmax(0,1fr)_330px]">

            <!-- SIDEBAR ESQUERDA -->
            <aside class="hidden h-full min-h-0 overflow-hidden border-r border-base-300/80 bg-base-100/70 p-4 backdrop-blur-xl lg:flex lg:flex-col">
                <div class="mb-4 flex shrink-0 items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-[0.2em] opacity-50">
                            Histórico
                        </h2>

                        <p class="text-xs opacity-60">
                            {{ conversations.length }} conversas
                        </p>
                    </div>

                    <Link
                        :href="route('ai-chat.index')"
                        class="btn btn-primary btn-sm rounded-xl"
                    >
                        + Nova
                    </Link>
                </div>

                <label class="mb-4 flex h-11 shrink-0 items-center gap-2 rounded-2xl border border-base-300 bg-base-200/70 px-4">
                    <span class="opacity-50">⌕</span>

                    <input
                        v-model="conversationSearch"
                        type="text"
                        class="min-w-0 grow bg-transparent text-sm outline-none border-none focus:ring-0 "
                        placeholder="Pesquisar..."
                    />
                </label>

                <div class="chat-scroll min-h-0 flex-1 space-y-2 overflow-y-auto pr-2 pt-2">
                    <Link
                        v-for="conversation in filteredConversations"
                        :key="conversation.id"
                        :href="route('ai-chat.show', conversation.id)"
                        class="group block rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-lg"
                        :class="activeConversation?.id === conversation.id
                            ? 'border-primary/50 bg-primary/10 shadow-lg shadow-primary/10'
                            : 'border-base-300 bg-base-200/60 hover:border-primary/30 hover:bg-base-200'"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-black"
                                :class="activeConversation?.id === conversation.id
                                    ? 'bg-primary text-primary-content'
                                    : 'bg-base-100'"
                            >
                                AI
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold">
                                    {{ conversation.title }}
                                </p>

                                <p class="mt-1 truncate text-xs opacity-60">
                                    {{ conversation.model_id || 'sem modelo' }}
                                </p>

                                <p class="mt-2 text-[11px] opacity-50">
                                    {{ formatDate(conversation.updated_at) }}
                                </p>
                            </div>
                        </div>
                    </Link>

                    <div
                        v-if="!filteredConversations.length"
                        class="rounded-2xl border border-dashed border-base-300 bg-base-200/50 p-6 text-center text-sm opacity-60"
                    >
                        Ainda não existem conversas.
                    </div>
                </div>
            </aside>

            <!-- ÁREA CENTRAL -->
            <section class="flex h-full min-h-0 flex-col overflow-hidden bg-base-100/70 backdrop-blur-xl">

                <!-- MOBILE TOP -->
                <div class="flex items-center justify-between border-b border-base-300/80 bg-base-100/80 p-3 lg:hidden">
                    <Link
                        :href="route('ai-chat.index')"
                        class="btn btn-primary btn-sm rounded-xl"
                    >
                        + Nova
                    </Link>

                    <button
                        type="button"
                        class="btn btn-outline btn-sm rounded-xl"
                        @click="showSettingsMobile = !showSettingsMobile"
                    >
                        Modelo
                    </button>
                </div>

                <!-- MOBILE CONFIG -->
                <div
                    v-if="showSettingsMobile"
                    class="border-b border-base-300 bg-base-100 p-4 lg:hidden"
                >
                    <div class="space-y-3">
                        <input
                            v-model="modelSearch"
                            type="text"
                            class="input input-bordered w-full rounded-2xl"
                            placeholder="Filtrar modelos..."
                        />

                        <select
                            v-model="form.model_id"
                            class="select select-bordered w-full rounded-2xl"
                        >
                            <option value="" disabled>
                                Seleciona um modelo
                            </option>

                            <option
                                v-for="model in filteredModels"
                                :key="model.id"
                                :value="model.id"
                            >
                                {{ model.name }}
                            </option>
                        </select>

                        <div class="grid grid-cols-2 gap-3">
                            <input
                                v-model.number="form.temperature"
                                type="number"
                                min="0"
                                max="2"
                                step="0.1"
                                class="input input-bordered rounded-2xl"
                                placeholder="Temperature"
                            />

                            <input
                                v-model.number="form.max_tokens"
                                type="number"
                                min="1"
                                max="8000"
                                step="1"
                                class="input input-bordered rounded-2xl"
                                placeholder="Max tokens"
                            />
                        </div>
                    </div>
                </div>

                <!-- CHAT HEADER -->
                <div class="border-b border-base-300/80 bg-base-100/80 p-4 backdrop-blur-xl">
                    <div class="mx-auto flex max-w-4xl items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="truncate text-xl font-black">
                                {{ activeConversation?.title || 'Nova conversa' }}
                            </h1>

                            <p class="truncate text-sm opacity-60">
                                {{ selectedModel?.name || 'Seleciona um modelo para começar' }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                v-if="activeConversation"
                                type="button"
                                class="btn btn-error btn-outline btn-sm gap-2 rounded-xl"
                                :disabled="streaming || deletingConversation"
                                @click="deleteConversation"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v2H9V5a1 1 0 011-1z"
                                    />
                                </svg>

                                Apagar
                            </button>

                            <div
                                class="badge gap-2 rounded-xl px-3 py-3 font-bold"
                                :class="openRouterConfigured ? 'badge-success' : 'badge-error'"
                            >
                                <span class="h-2 w-2 rounded-full bg-current"></span>
                                {{ openRouterConfigured ? 'Pronto' : 'Sem API Key' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MENSAGENS -->
                <div
                    ref="messagesBox"
                    class="chat-scroll min-h-0 flex-1 overflow-y-auto scroll-smooth px-4 py-4 md:px-6"
                    @scroll="handleMessagesScroll"
                >
                    <div class="mx-auto max-w-4xl">

                        <!-- ECRÃ INICIAL -->
                        <div
                            v-if="!activeConversation && !activeMessages.length"
                            class="flex min-h-[38vh] items-center justify-center py-4"
                        >
                            <div class="w-full max-w-3xl text-center">
                                <div class="relative mx-auto mb-5 flex h-20 w-20 items-center justify-center">
                                    <div class="absolute inset-0 rounded-[2rem] bg-primary/30 blur-2xl"></div>

                                    <div class="relative flex h-20 w-20 items-center justify-center rounded-[1.7rem] bg-gradient-to-br from-primary to-secondary text-2xl font-black text-primary-content shadow-2xl shadow-primary/25">
                                        AI
                                    </div>
                                </div>

                                <h2 class="text-3xl font-black tracking-tight md:text-5xl">
                                    Como posso ajudar?
                                </h2>

                                <p class="mx-auto mt-4 max-w-2xl text-base opacity-60">
                                    Escolhe um modelo OpenRouter, escreve a primeira mensagem e começa uma conversa com histórico guardado.
                                </p>

                                <div class="mt-6 grid gap-3 md:grid-cols-2">
                                    <button
                                        v-for="suggestion in promptSuggestions"
                                        :key="suggestion.title"
                                        type="button"
                                        class="group rounded-[1.35rem] border border-base-300 bg-base-100/80 p-4 text-left shadow-sm transition hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl hover:shadow-primary/10"
                                        @click="useSuggestion(suggestion.text)"
                                    >
                                        <div class="flex items-center gap-4">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-base-200 text-xl transition group-hover:bg-primary group-hover:text-primary-content">
                                                {{ suggestion.icon }}
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <p class="font-black">
                                                    {{ suggestion.title }}
                                                </p>

                                                <p class="truncate text-sm opacity-60">
                                                    {{ suggestion.text }}
                                                </p>
                                            </div>

                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-base-200 transition group-hover:bg-primary group-hover:text-primary-content">
                                                +
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- CONVERSA -->
                        <div v-else class="space-y-6 pb-6">
                            <div
                                v-for="message in activeMessages"
                                :key="message.id"
                                class="animate-message-in group flex gap-4"
                                :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    v-if="message.role !== 'user'"
                                    class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-secondary text-xs font-black text-primary-content shadow-lg shadow-primary/20"
                                >
                                    {{ roleInitial(message.role) }}
                                </div>

                                <div
                                    class="overflow-x-auto rounded-[1.5rem] border px-5 py-4 shadow-sm"
                                    :class="message.role === 'user'
                                        ? 'max-w-[82%] border-primary/30 bg-primary text-primary-content shadow-primary/10'
                                        : 'max-w-[96%] border-base-300 bg-base-100/85'"
                                >
                                    <div class="mb-2 flex items-center justify-between gap-4 text-xs">
                                        <span class="font-black opacity-70">
                                            {{ roleLabel(message.role) }}
                                        </span>

                                        <div class="flex items-center gap-2">
                                            <button
                                                v-if="message.role === 'assistant' && message.content"
                                                type="button"
                                                class="btn btn-ghost btn-xs gap-1 rounded-lg px-2"
                                                :title="copiedMessageId === message.id ? 'Copiado' : 'Copiar resposta'"
                                                @click="copyMessage(message)"
                                            >
                                                <svg
                                                    v-if="copiedMessageId !== message.id"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2M10 8h8a2 2 0 012 2v8a2 2 0 01-2 2h-8a2 2 0 01-2-2v-8a2 2 0 012-2z"
                                                    />
                                                </svg>

                                                <svg
                                                    v-else
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4 text-success"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M11 14l2 2 4-4M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2M10 8h8a2 2 0 012 2v8a2 2 0 01-2 2h-8a2 2 0 01-2-2v-8a2 2 0 012-2z"
                                                    />
                                                </svg>

                                                <span class="hidden sm:inline">
                                                    {{ copiedMessageId === message.id ? 'Copiado' : 'Copiar' }}
                                                </span>
                                            </button>

                                            <span class="opacity-50">
                                                {{ formatTime(message.created_at) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="leading-7">
                                        <div
                                            v-if="message.content && message.role === 'assistant'"
                                            class="ai-markdown"
                                            v-html="renderMarkdown(message.content)"
                                        ></div>

                                        <div
                                            v-else-if="message.content"
                                            class="whitespace-pre-line"
                                        >
                                            {{ message.content }}
                                        </div>

                                        <span
                                            v-if="message.streaming && !message.content"
                                            class="ml-1 inline-flex items-center gap-1 align-middle"
                                        >
                                            <span class="loading loading-dots loading-sm"></span>
                                        </span>
                                    </div>

                                    <div
                                        v-if="message.model_id"
                                        class="mt-3 truncate text-xs opacity-50"
                                    >
                                        {{ message.model_id }}
                                    </div>
                                </div>

                                <div
                                    v-if="message.role === 'user'"
                                    class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-neutral text-xs font-black text-neutral-content"
                                >
                                    {{ roleInitial(message.role) }}
                                </div>
                            </div>
                        </div>
                        <div ref="bottomAnchor" class="h-1"></div>
                    </div>
                </div>

                <!-- INPUT -->
                <div class="shrink-0 border-t border-base-300/80 bg-base-100/95 px-3 py-2 backdrop-blur-xl">
                    <form
                        class="mx-auto max-w-4xl"
                        @submit.prevent="submit"
                    >
                        <div class="rounded-[1.35rem] border border-base-300 bg-base-100 p-2 shadow-xl shadow-base-content/5 ring-1 ring-primary/10">
                            <textarea
                                v-model="form.message"
                                rows="1"
                                class="chat-scroll textarea max-h-28 min-h-11 w-full resize-none overflow-y-auto border-0 bg-transparent py-2 text-base leading-6 outline-none focus:outline-none"
                                placeholder="Escreve a tua mensagem..."
                                :disabled="streaming || !openRouterConfigured"
                                @keydown.enter.exact.prevent="submit"
                            ></textarea>

                            <div class="flex items-center justify-between gap-2 border-t border-base-300 pt-2">
                                <div class="hidden flex-wrap items-center gap-2 text-xs opacity-60 xl:flex">
                                    <span class="badge badge-ghost rounded-xl">
                                        Enter envia
                                    </span>

                                    <span class="badge badge-ghost rounded-xl">
                                        Shift + Enter quebra linha
                                    </span>

                                    <span class="badge badge-ghost rounded-xl">
                                        {{ form.message.length }}/10000
                                    </span>
                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm rounded-xl px-5 shadow-lg shadow-primary/20"
                                    :disabled="!canSubmit"
                                >
                                    <span
                                        v-if="streaming"
                                        class="loading loading-spinner loading-sm"
                                    ></span>

                                    <span v-if="streaming">
                                        A enviar
                                    </span>

                                    <span v-else>
                                        Enviar
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="streamError || formErrors.message || formErrors.model_id || formErrors.temperature || formErrors.max_tokens"
                            class="alert alert-error mt-3 rounded-2xl"
                        >
                            <div>
                                <p v-if="streamError">
                                    {{ streamError }}
                                </p>

                                <p v-if="formErrors.message">
                                    {{ formErrors.message[0] }}
                                </p>

                                <p v-if="formErrors.model_id">
                                    {{ formErrors.model_id[0] }}
                                </p>

                                <p v-if="formErrors.temperature">
                                    {{ formErrors.temperature[0] }}
                                </p>

                                <p v-if="formErrors.max_tokens">
                                    {{ formErrors.max_tokens[0] }}
                                </p>
                            </div>
                        </div>

                        <p
                            v-if="!openRouterConfigured"
                            class="mt-3 text-center text-sm text-error"
                        >
                            A chave OpenRouter não está configurada. Define OPENROUTER_API_KEY no .env.
                        </p>
                    </form>
                </div>
            </section>

            <!-- PAINEL DIREITO -->
            <aside class="hidden h-full min-h-0 overflow-y-auto border-l border-base-300/80 bg-base-100/70 p-4 backdrop-blur-xl xl:flex xl:flex-col">
                <div class="mb-4">
                    <h2 class="text-sm font-black uppercase tracking-[0.2em] opacity-50">
                        Configuração
                    </h2>

                    <p class="mt-1 text-xs opacity-60">
                        Modelo e parâmetros da conversa
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-[1.5rem] border border-base-300 bg-base-200/60 p-4">
                        <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] opacity-50">
                            Filtrar modelos
                        </label>

                        <input
                            v-model="modelSearch"
                            type="text"
                            class="input input-bordered w-full rounded-2xl bg-base-100"
                            placeholder="Ex: free, gpt, gemini..."
                        />
                    </div>

                    <div class="rounded-[1.5rem] border border-base-300 bg-base-200/60 p-4">
                        <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] opacity-50">
                            Modelo
                        </label>

                        <select
                            v-model="form.model_id"
                            class="select select-bordered w-full rounded-2xl bg-base-100"
                        >
                            <option value="" disabled>
                                Seleciona um modelo
                            </option>

                            <option
                                v-for="model in filteredModels"
                                :key="model.id"
                                :value="model.id"
                            >
                                {{ model.name }}
                            </option>
                        </select>

                        <p class="mt-3 break-all text-xs opacity-50">
                            {{ selectedModel?.id || 'Nenhum modelo selecionado' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-[1.5rem] border border-base-300 bg-base-200/60 p-4">
                            <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] opacity-50">
                                Temp.
                            </label>

                            <input
                                v-model.number="form.temperature"
                                type="number"
                                min="0"
                                max="2"
                                step="0.1"
                                class="input input-bordered w-full rounded-2xl bg-base-100"
                            />
                        </div>

                        <div class="rounded-[1.5rem] border border-base-300 bg-base-200/60 p-4">
                            <label class="mb-2 block text-xs font-black uppercase tracking-[0.18em] opacity-50">
                                Tokens
                            </label>

                            <input
                                v-model.number="form.max_tokens"
                                type="number"
                                min="1"
                                max="8000"
                                step="1"
                                class="input input-bordered w-full rounded-2xl bg-base-100"
                            />
                        </div>
                    </div>

                    <div
                        class="rounded-[1.5rem] border p-4"
                        :class="openRouterConfigured
                            ? 'border-success/30 bg-success/10'
                            : 'border-error/30 bg-error/10'"
                    >
                        <p class="text-sm font-black">
                            {{ openRouterConfigured ? 'OpenRouter ativo' : 'OpenRouter inativo' }}
                        </p>

                        <p class="mt-1 text-xs opacity-70">
                            {{ openRouterConfigured
                                ? 'A chave API está configurada no backend.'
                                : 'Define OPENROUTER_API_KEY no .env.' }}
                        </p>
                    </div>

                    <div
                        v-if="modelsError"
                        class="alert alert-error rounded-2xl text-sm"
                    >
                        {{ modelsError }}
                    </div>

                    <div class="rounded-[1.5rem] border border-base-300 bg-base-200/60 p-4">
                        <p class="text-sm font-black">
                            Modelos carregados
                        </p>

                        <p class="mt-1 text-3xl font-black text-primary">
                            {{ models.length }}
                        </p>

                        <p class="mt-1 text-xs opacity-60">
                            A lista vem diretamente da API do OpenRouter.
                        </p>
                    </div>
                </div>
            </aside>

        </div>

        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-black/45 p-4 backdrop-blur-sm"
            @click.self="closeDeleteModal"
        >
            <div class="w-full max-w-md animate-message-in rounded-[1.75rem] border border-base-300 bg-base-100 p-6 shadow-2xl">
                <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-error/10 text-error">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-7 w-7"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                        />
                    </svg>
                </div>

                <div class="text-center">
                    <h3 class="text-xl font-black">
                        Apagar conversa?
                    </h3>

                    <p class="mt-2 text-sm opacity-70">
                        Esta ação vai apagar a conversa
                        <span class="font-bold">
                            “{{ activeConversation?.title }}”
                        </span>
                        e todas as mensagens associadas. Não é possível anular.
                    </p>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="btn btn-ghost rounded-xl"
                        :disabled="deletingConversation"
                        @click="closeDeleteModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="btn btn-error gap-2 rounded-xl"
                        :disabled="deletingConversation"
                        @click="confirmDeleteConversation"
                    >
                        <span
                            v-if="deletingConversation"
                            class="loading loading-spinner loading-sm"
                        ></span>

                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m3-3h4a1 1 0 011 1v2H9V5a1 1 0 011-1z"
                            />
                        </svg>

                        {{ deletingConversation ? 'A apagar...' : 'Apagar conversa' }}
                    </button>
                </div>
            </div>
        </div>
    </AiChatLayout>
</template>

<style scoped>
@keyframes messageIn {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.985);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.animate-message-in {
    animation: messageIn 220ms ease-out both;
}

.chat-scroll {
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, currentColor 22%, transparent) transparent;
}

.chat-scroll::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.chat-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.chat-scroll::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: color-mix(in srgb, currentColor 18%, transparent);
}

.chat-scroll::-webkit-scrollbar-thumb:hover {
    background: color-mix(in srgb, currentColor 30%, transparent);
}

.ai-markdown {
    overflow-wrap: anywhere;
}

.ai-markdown :deep(:first-child) {
    margin-top: 0;
}

.ai-markdown :deep(:last-child) {
    margin-bottom: 0;
}

.ai-markdown :deep(h1),
.ai-markdown :deep(h2),
.ai-markdown :deep(h3) {
    margin-top: 1rem;
    margin-bottom: 0.75rem;
    font-weight: 900;
    line-height: 1.2;
}

.ai-markdown :deep(h1) {
    font-size: 1.55rem;
}

.ai-markdown :deep(h2) {
    font-size: 1.35rem;
}

.ai-markdown :deep(h3) {
    font-size: 1.15rem;
}

.ai-markdown :deep(p) {
    margin-bottom: 0.85rem;
}

.ai-markdown :deep(strong) {
    font-weight: 900;
}

.ai-markdown :deep(em) {
    font-style: italic;
}

.ai-markdown :deep(ul),
.ai-markdown :deep(ol) {
    margin: 0.75rem 0 1rem 1.25rem;
}

.ai-markdown :deep(ul) {
    list-style: disc;
}

.ai-markdown :deep(ol) {
    list-style: decimal;
}

.ai-markdown :deep(li) {
    margin-bottom: 0.35rem;
}

.ai-markdown :deep(blockquote) {
    margin: 1rem 0;
    border-left: 4px solid hsl(var(--p));
    padding-left: 1rem;
    opacity: 0.85;
}

.ai-markdown :deep(code) {
    border-radius: 0.5rem;
    background: hsl(var(--b2));
    padding: 0.15rem 0.4rem;
    font-size: 0.9em;
}

.ai-markdown :deep(pre) {
    margin: 1rem 0;
    max-width: 100%;
    overflow-x: auto;
    border-radius: 1rem;
    background: hsl(var(--b2));
    padding: 1rem;
}

.ai-markdown :deep(pre code) {
    background: transparent;
    padding: 0;
}

.ai-markdown :deep(a) {
    color: hsl(var(--p));
    text-decoration: underline;
    text-underline-offset: 3px;
}

.ai-markdown :deep(table) {
    margin: 1rem 0;
    width: 100%;
    border-collapse: collapse;
    overflow: hidden;
    border-radius: 1rem;
    font-size: 0.92rem;
}

.ai-markdown :deep(th),
.ai-markdown :deep(td) {
    border: 1px solid hsl(var(--b3));
    padding: 0.75rem;
    vertical-align: top;
}

.ai-markdown :deep(th) {
    background: hsl(var(--b2));
    font-weight: 900;
}

.ai-markdown :deep(td) {
    background: hsl(var(--b1));
}

.ai-markdown :deep(hr) {
    margin: 1.25rem 0;
    border-color: hsl(var(--b3));
}
</style>