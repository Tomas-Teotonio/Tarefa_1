<script setup>
import { Head, Link } from '@inertiajs/vue3'
import MarkdownIt from 'markdown-it'

const props = defineProps({
    conversation: Object,
    share: Object,
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

const formatDateTime = (date) => {
    if (!date) return '-'

    return new Date(date).toLocaleString('pt-PT', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const roleLabel = (role) => {
    if (role === 'user') return 'Utilizador'
    if (role === 'assistant') return 'IA'

    return role
}
</script>

<template>
    <Head :title="conversation.title || 'Conversa partilhada'" />

    <div class="min-h-screen bg-base-200 text-base-content">
        <div class="border-b border-base-300 bg-base-100/90 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] opacity-50">
                        Conversa partilhada
                    </p>

                    <h1 class="mt-1 text-xl font-black">
                        {{ conversation.title || 'Conversa sem título' }}
                    </h1>
                </div>

                <Link
                    :href="route('login')"
                    class="btn btn-outline btn-sm rounded-xl"
                >
                    Entrar
                </Link>
            </div>
        </div>

        <main class="mx-auto max-w-5xl px-4 py-6">
            <div class="mb-6 rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm">
                <div class="grid gap-3 text-sm md:grid-cols-4">
                    <p>
                        <strong>Tipo:</strong>
                        {{ share.visibility === 'public' ? 'Público' : 'Restrito' }}
                    </p>

                    <p>
                        <strong>Modelo:</strong>
                        {{ conversation.model_id || 'Sem modelo' }}
                    </p>

                    <p>
                        <strong>Criada:</strong>
                        {{ formatDateTime(conversation.created_at) }}
                    </p>

                    <p>
                        <strong>Mensagens:</strong>
                        {{ conversation.messages.length }}
                    </p>
                </div>
            </div>

            <div class="space-y-5">
                <div
                    v-for="message in conversation.messages"
                    :key="message.id"
                    class="flex gap-4"
                    :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div
                        class="max-w-[92%] rounded-[1.5rem] border px-5 py-4 shadow-sm"
                        :class="message.role === 'user'
                            ? 'border-primary/30 bg-primary text-primary-content'
                            : 'border-base-300 bg-base-100'"
                    >
                        <div class="mb-3 flex items-center justify-between gap-4 text-xs">
                            <span class="font-black opacity-70">
                                {{ roleLabel(message.role) }}
                            </span>

                            <span class="opacity-50">
                                {{ formatDateTime(message.created_at) }}
                            </span>
                        </div>

                        <div
                            v-if="message.role === 'assistant'"
                            class="ai-markdown leading-7"
                            v-html="renderMarkdown(message.content)"
                        ></div>

                        <div
                            v-else
                            class="whitespace-pre-line leading-7"
                        >
                            {{ message.content }}
                        </div>

                        <p
                            v-if="message.model_id"
                            class="mt-3 truncate text-xs opacity-50"
                        >
                            {{ message.model_id }}
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
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

.ai-markdown :deep(table) {
    margin: 1rem 0;
    width: 100%;
    border-collapse: collapse;
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
}
</style>