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

    prompts: {
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

const historySearchResults = ref([])
const historySearchLoading = ref(false)
const historySearchError = ref(null)
let historySearchTimer = null

const highlightedMessageId = ref(null)
const highlightedSearchTerm = ref('')

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

const messageInput = ref(null)

const promptSearch = ref('')
const showPromptForm = ref(false)
const editingPromptId = ref(null)
const savingPrompt = ref(false)
const deletingPromptId = ref(null)
const promptErrors = ref({})

const commentEditorMessageId = ref(null)
const editingCommentId = ref(null)
const savingComment = ref(false)
const deletingCommentId = ref(null)
const commentErrors = ref({})

const commentForm = reactive({
    content: '',
})

const promptForm = reactive({
    name: '',
    content: '',
})

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

const attachmentInput = ref(null)
const attachmentFile = ref(null)
const attachmentError = ref(null)

const attachmentInspecting = ref(false)
const attachmentInspection = ref(null)

let attachmentInspectionSequence = 0

const allowedAttachmentExtensions = [
    'txt',
    'md',
    'pdf',
    'js',
    'ts',
    'php',
    'py',
    'html',
    'css',
    'json',
    'xml',
    'csv',
]

const localMessages = ref(
    props.activeConversation?.messages
        ? [...props.activeConversation.messages]
        : []
)

let typingQueue = []
let typingTimer = null

const isSearchingHistory = computed(() => {
    return conversationSearch.value.trim().length >= 2
})

const filteredConversations = computed(() => {
    return props.conversations
})

const pinnedConversations = computed(() => {
    return filteredConversations.value.filter(conversation => conversation.pinned_at)
})

const unpinnedConversations = computed(() => {
    return filteredConversations.value.filter(conversation => !conversation.pinned_at)
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

const filteredPrompts = computed(() => {
    const term = promptSearch.value.trim().toLowerCase()

    if (!term) {
        return props.prompts
    }

    return props.prompts.filter(prompt => {
        return prompt.name?.toLowerCase().includes(term)
    })
})

const activeMessages = computed(() => {
    return localMessages.value
})

const selectedModel = computed(() => {
    return props.models.find(model => model.id === form.model_id)
})


const estimateTextTokens = (text) => {
    const length = String(text ?? '').length

    return length
        ? Math.ceil(length / 4)
        : 0
}

const estimatedHistoryTokens = computed(() => {
    return activeMessages.value.reduce((total, message) => {
        return total + estimateTextTokens(message.content)
    }, 0)
})

const attachmentContextInfo = computed(() => {
    if (!attachmentInspection.value) {
        return null
    }

    const modelContextLength = Number(
        selectedModel.value?.context_length ?? 0
    )

    const responseTokens = Number(
        form.max_tokens ?? 0
    )

    const messageTokens = estimateTextTokens(
        form.message
    )

    const attachmentTokens = Number(
        attachmentInspection.value.estimated_tokens ?? 0
    )

    const safetyMargin = 500

    const estimatedTotalTokens =
        estimatedHistoryTokens.value
        + messageTokens
        + attachmentTokens
        + responseTokens
        + safetyMargin

    const exceedsContext =
        modelContextLength > 0
        && estimatedTotalTokens > modelContextLength

    const wasTruncated = Boolean(
        attachmentInspection.value.was_truncated
    )

    let warning = null

    if (exceedsContext) {
        warning = 'O ficheiro e o histórico da conversa podem ultrapassar o limite de contexto do modelo selecionado.'
    } else if (wasTruncated) {
        warning = 'O ficheiro é muito extenso. Parte do conteúdo será truncada antes de ser enviada ao modelo.'
    }

    return {
        modelContextLength,
        responseTokens,
        messageTokens,
        attachmentTokens,
        estimatedTotalTokens,
        exceedsContext,
        wasTruncated,
        warning,
    }
})

const canSubmit = computed(() => {
    return props.openRouterConfigured
        && form.model_id
        && form.message.trim()
        && form.message.length <= 10000
        && !streaming.value
        && !attachmentInspecting.value
        && !attachmentError.value
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

const handleStreamEvent = (
    payload,
    assistantMessage,
    userMessage
) => {
    if (!payload) return

    if (payload.event === 'conversation') {
        createdConversation.value = payload.data

        if (payload.data.user_message_id) {
            userMessage.id = payload.data.user_message_id
        }
    }

    if (payload.event === 'delta') {
        queueAssistantText(
            payload.data.content ?? '',
            assistantMessage
        )
    }

    if (payload.event === 'error') {
        streamError.value = payload.data.message
            ?? 'Erro ao receber resposta da IA.'
    }

    if (payload.event === 'done') {
        if (payload.data.message_id) {
            assistantMessage.id = payload.data.message_id
        }

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

const togglePinConversation = (conversation) => {
    if (!conversation || streaming.value) return

    router.patch(route('ai-chat.pin', conversation.id), {}, {
        preserveScroll: true,
        onError: (errors) => {
            streamError.value = errors.pin ?? 'Não foi possível atualizar a conversa fixada.'
        },
    })
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

const resetPromptForm = () => {
    promptForm.name = ''
    promptForm.content = ''
    editingPromptId.value = null
    promptErrors.value = {}
}

const openPromptCreate = () => {
    resetPromptForm()
    showPromptForm.value = true
}

const openPromptEdit = (prompt) => {
    promptForm.name = prompt.name
    promptForm.content = prompt.content
    editingPromptId.value = prompt.id
    promptErrors.value = {}
    showPromptForm.value = true
}

const closePromptForm = () => {
    if (savingPrompt.value) return

    resetPromptForm()
    showPromptForm.value = false
}

const savePrompt = () => {
    if (savingPrompt.value) return

    savingPrompt.value = true
    promptErrors.value = {}

    const payload = {
        name: promptForm.name,
        content: promptForm.content,
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetPromptForm()
            showPromptForm.value = false
        },
        onError: (errors) => {
            promptErrors.value = errors
        },
        onFinish: () => {
            savingPrompt.value = false
        },
    }

    if (editingPromptId.value) {
        router.put(route('ai-prompts.update', editingPromptId.value), payload, options)
        return
    }

    router.post(route('ai-prompts.store'), payload, options)
}

const removePrompt = (prompt) => {
    const confirmed = window.confirm(`Queres eliminar o prompt “${prompt.name}”?`)

    if (!confirmed) return

    deletingPromptId.value = prompt.id

    router.delete(route('ai-prompts.destroy', prompt.id), {
        preserveScroll: true,
        onFinish: () => {
            deletingPromptId.value = null
        },
    })
}

const insertPrompt = async (prompt) => {
    const content = prompt.content.trim()

    if (!content) return

    form.message = form.message.trim()
        ? `${form.message.trim()}\n\n${content}`
        : content

    await nextTick()

    messageInput.value?.focus()
}

const searchHistory = async () => {
    const term = conversationSearch.value.trim()

    historySearchError.value = null

    if (term.length < 2) {
        historySearchResults.value = []
        historySearchLoading.value = false
        return
    }

    historySearchLoading.value = true

    try {
        const response = await fetch(route('ai-chat.search') + `?q=${encodeURIComponent(term)}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })

        if (!response.ok) {
            throw new Error('Erro ao pesquisar no histórico.')
        }

        const json = await response.json()

        historySearchResults.value = json.results ?? []
    } catch (error) {
        historySearchError.value = error.message
        historySearchResults.value = []
    } finally {
        historySearchLoading.value = false
    }
}

const openSearchResult = (result) => {
    if (!result?.url) return

    const url = new URL(result.url, window.location.origin)
    const term = result.search_term || conversationSearch.value.trim()

    if (term.length >= 2 && !url.searchParams.get('q')) {
        url.searchParams.set('q', term)
    }

    router.visit(`${url.pathname}${url.search}${url.hash}`, {
        preserveScroll: false,
    })
}
const resultRoleLabel = (role) => {
    if (role === 'user') return 'Tu'
    if (role === 'assistant') return 'IA'

    return 'Conversa'
}

const normalizeForHighlight = (value) => {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
}

const escapeHtml = (value) => {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;')
}

const findHighlightRanges = (text, term) => {
    const value = String(text ?? '')
    const search = normalizeForHighlight(term).trim()

    if (search.length < 2) return []

    let normalizedText = ''
    const indexMap = []
    let originalOffset = 0

    for (const char of Array.from(value)) {
        const start = originalOffset
        const end = start + char.length
        const normalizedChar = normalizeForHighlight(char)

        for (const part of Array.from(normalizedChar)) {
            normalizedText += part
            indexMap.push({ start, end })
        }

        originalOffset = end
    }

    const ranges = []
    let index = 0

    while ((index = normalizedText.indexOf(search, index)) !== -1) {
        const start = indexMap[index]?.start
        const end = indexMap[index + search.length - 1]?.end

        if (
            start !== undefined
            && end !== undefined
            && (!ranges.length || start >= ranges[ranges.length - 1].end)
        ) {
            ranges.push({ start, end })
        }

        index += search.length
    }

    return ranges
}

const highlightPlainText = (text, term) => {
    const value = String(text ?? '')
    const ranges = findHighlightRanges(value, term)

    if (!ranges.length) {
        return escapeHtml(value)
    }

    let html = ''
    let cursor = 0

    for (const range of ranges) {
        html += escapeHtml(value.slice(cursor, range.start))
        html += `<mark class="ai-search-mark">${escapeHtml(value.slice(range.start, range.end))}</mark>`
        cursor = range.end
    }

    html += escapeHtml(value.slice(cursor))

    return html
}

const highlightHtml = (html, term) => {
    if (!term?.trim() || typeof window === 'undefined' || typeof DOMParser === 'undefined') {
        return html
    }

    const parser = new DOMParser()
    const parsed = parser.parseFromString(`<div>${html}</div>`, 'text/html')
    const root = parsed.body.firstElementChild

    if (!root) return html

    const walker = parsed.createTreeWalker(
        root,
        window.NodeFilter.SHOW_TEXT
    )

    const textNodes = []
    let node

    while ((node = walker.nextNode())) {
        if (node.nodeValue?.trim()) {
            textNodes.push(node)
        }
    }

    for (const textNode of textNodes) {
        const value = textNode.nodeValue
        const ranges = findHighlightRanges(value, term)

        if (!ranges.length) continue

        const fragment = parsed.createDocumentFragment()
        let cursor = 0

        for (const range of ranges) {
            fragment.appendChild(
                parsed.createTextNode(value.slice(cursor, range.start))
            )

            const mark = parsed.createElement('mark')
            mark.className = 'ai-search-mark'
            mark.textContent = value.slice(range.start, range.end)

            fragment.appendChild(mark)

            cursor = range.end
        }

        fragment.appendChild(
            parsed.createTextNode(value.slice(cursor))
        )

        textNode.parentNode.replaceChild(fragment, textNode)
    }

    return root.innerHTML
}

const shouldHighlightMessage = (message) => {
    return String(highlightedMessageId.value) === String(message?.id)
        && highlightedSearchTerm.value.trim().length >= 2
}

const renderAssistantMessage = (message) => {
    const html = renderMarkdown(message.content)

    if (!shouldHighlightMessage(message)) {
        return html
    }

    return highlightHtml(html, highlightedSearchTerm.value)
}

const renderUserMessage = (message) => {
    if (!shouldHighlightMessage(message)) {
        return escapeHtml(message.content)
    }

    return highlightPlainText(message.content, highlightedSearchTerm.value)
}

const renderSearchExcerpt = (excerpt) => {
    return highlightPlainText(excerpt, conversationSearch.value.trim())
}

const inspectAttachment = async (file) => {
    if (!file) return

    const currentSequence = ++attachmentInspectionSequence

    attachmentInspecting.value = true
    attachmentInspection.value = null
    attachmentError.value = null

    const payload = new FormData()

    payload.append('attachment', file)

    try {
        const response = await fetch(
            route('ai-chat.attachments.inspect'),
            {
                method: 'POST',
                credentials: 'same-origin',

                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },

                body: payload,
            }
        )

        let json = {}

        try {
            json = await response.json()
        } catch {
            //
        }

        /*
         * Ignora uma resposta antiga quando o utilizador
         * já selecionou ou removeu outro ficheiro.
         */
        if (currentSequence !== attachmentInspectionSequence) {
            return
        }

        if (!response.ok) {
            throw new Error(
                json.message
                ?? 'Não foi possível analisar o ficheiro.'
            )
        }

        attachmentInspection.value = json
    } catch (error) {
        if (currentSequence !== attachmentInspectionSequence) {
            return
        }

        attachmentError.value = error.message
            ?? 'Não foi possível analisar o ficheiro.'

        attachmentFile.value = null

        if (attachmentInput.value) {
            attachmentInput.value.value = ''
        }
    } finally {
        if (currentSequence === attachmentInspectionSequence) {
            attachmentInspecting.value = false
        }
    }
}

const handleAttachmentChange = async (event) => {
    attachmentError.value = null
    attachmentInspection.value = null

    const file = event.target.files?.[0]

    if (!file) {
        attachmentFile.value = null
        return
    }

    const extension = file.name
        .split('.')
        .pop()
        ?.toLowerCase()

    if (!allowedAttachmentExtensions.includes(extension)) {
        attachmentFile.value = null
        attachmentError.value = 'Tipo de ficheiro não permitido.'
        event.target.value = ''
        return
    }

    if (file.size > 5 * 1024 * 1024) {
        attachmentFile.value = null
        attachmentError.value = 'O ficheiro não pode ter mais de 5 MB.'
        event.target.value = ''
        return
    }

    attachmentFile.value = file

    await inspectAttachment(file)
}

const clearAttachment = () => {
    attachmentInspectionSequence++

    attachmentFile.value = null
    attachmentError.value = null
    attachmentInspection.value = null
    attachmentInspecting.value = false

    if (attachmentInput.value) {
        attachmentInput.value.value = ''
    }
}

const formatFileSize = (bytes) => {
    if (!bytes) return '0 KB'

    const kb = bytes / 1024

    if (kb < 1024) {
        return `${kb.toFixed(1)} KB`
    }

    return `${(kb / 1024).toFixed(2)} MB`
}

const messageComments = (message) => {
    return Array.isArray(message?.comments)
        ? message.comments
        : []
}

const isPersistedMessage = (message) => {
    if (!message?.id) return false

    return !String(message.id).startsWith('temp-')
}

const resetCommentForm = () => {
    commentForm.content = ''
    editingCommentId.value = null
    commentErrors.value = {}
}

const openCommentCreate = (message) => {
    if (
        !isPersistedMessage(message)
        || streaming.value
        || savingComment.value
    ) {
        return
    }

    resetCommentForm()

    commentEditorMessageId.value = message.id
}

const openCommentEdit = (message, comment) => {
    if (
        !isPersistedMessage(message)
        || streaming.value
        || savingComment.value
    ) {
        return
    }

    commentEditorMessageId.value = message.id
    editingCommentId.value = comment.id
    commentForm.content = comment.content
    commentErrors.value = {}
}

const closeCommentEditor = () => {
    if (savingComment.value) return

    commentEditorMessageId.value = null
    resetCommentForm()
}

const saveMessageComment = (message) => {
    if (
        !isPersistedMessage(message)
        || savingComment.value
        || !commentForm.content.trim()
    ) {
        return
    }

    savingComment.value = true
    commentErrors.value = {}

    const payload = {
        content: commentForm.content.trim(),
    }

    const options = {
        preserveScroll: true,

        onSuccess: () => {
            closeCommentEditor()
        },

        onError: (errors) => {
            commentErrors.value = errors
        },

        onFinish: () => {
            savingComment.value = false
        },
    }

    if (editingCommentId.value) {
        router.put(
            route(
                'ai-message-comments.update',
                editingCommentId.value
            ),
            payload,
            options
        )

        return
    }

    router.post(
        route(
            'ai-message-comments.store',
            message.id
        ),
        payload,
        options
    )
}

const removeMessageComment = (comment) => {
    if (
        !comment
        || deletingCommentId.value
        || streaming.value
    ) {
        return
    }

    const confirmed = window.confirm(
        'Tens a certeza que queres eliminar este comentário?'
    )

    if (!confirmed) return

    deletingCommentId.value = comment.id

    router.delete(
        route(
            'ai-message-comments.destroy',
            comment.id
        ),
        {
            preserveScroll: true,

            onFinish: () => {
                deletingCommentId.value = null
            },
        }
    )
}

const submit = async () => {
    if (!canSubmit.value) return

    const userText = form.message.trim()
    const selectedAttachment = attachmentFile.value

    streaming.value = true
    streamError.value = null
    formErrors.value = {}
    shouldAutoScroll.value = true

    const now = new Date().toISOString()

    const userMessage = reactive({
        id: `temp-user-${Date.now()}`,
        role: 'user',
        content: selectedAttachment
            ? `${userText}\n\n📎 Ficheiro anexado: ${selectedAttachment.name}`
            : userText,
        model_id: form.model_id,
        created_at: now,
        comments: [],
    })

    const assistantMessage = reactive({
        id: `temp-assistant-${Date.now()}`,
        role: 'assistant',
        content: '',
        model_id: form.model_id,
        created_at: now,
        streaming: true,
        comments: [],
    })

    localMessages.value.push(userMessage)
    localMessages.value.push(assistantMessage)

    form.message = ''
    clearAttachment()

    await scrollToBottom(true)

    try {
        const payload = new FormData()

        payload.append('conversation_id', props.activeConversation?.id ?? createdConversation.value?.id ?? '')
        payload.append('message', userText)
        payload.append('model_id', form.model_id)
        payload.append('temperature', form.temperature)
        payload.append('max_tokens', form.max_tokens)

        if (selectedAttachment) {
            payload.append('attachment', selectedAttachment)
        }

        const response = await fetch(route('ai-chat.stream'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'text/event-stream',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload,
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
                handleStreamEvent(
                    payload,
                    assistantMessage,
                    userMessage
                )
            }
        }

        if (buffer.trim()) {
            const payload = parseSseBlock(buffer.trim())
            handleStreamEvent(
                payload,
                assistantMessage,
                userMessage
            )
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
        commentEditorMessageId.value = null
        deletingCommentId.value = null
        resetCommentForm()

        form.model_id = props.activeConversation?.model_id || preferredModel.value?.id || ''
        form.temperature = Number(props.activeConversation?.temperature ?? 0.7)
        form.max_tokens = Number(props.activeConversation?.max_tokens ?? 1024)
        form.message = ''
        clearAttachment()

        localMessages.value = props.activeConversation?.messages
            ? [...props.activeConversation.messages]
            : []

        createdConversation.value = null
        streamError.value = null
        formErrors.value = {}

        clearTypingAnimation()

        nextTick(() => {
            scrollToBottom(false)

            window.setTimeout(() => {
                scrollToHighlightedMessage()
            }, 250)
        })
    }
)

watch(conversationSearch, () => {
    clearTimeout(historySearchTimer)

    historySearchTimer = window.setTimeout(() => {
        searchHistory()
    }, 350)
})

watch(
    () => props.activeConversation?.messages,
    (messages) => {
        if (streaming.value) return

        localMessages.value = messages
            ? messages.map(message => ({
                ...message,
                comments: [...(message.comments ?? [])],
            }))
            : []
    },
    {
        deep: true,
    }
)

watch(
    () => form.message,
    (message) => {
        if (message.length > 10000) {
            form.message = message.slice(0, 10000)
        }
    }
)

onMounted(() => {
    updateHighlightedMessageFromUrl()
    scrollToBottom(false)

    window.setTimeout(() => {
        scrollToHighlightedMessage()
    }, 250)
})

onBeforeUnmount(() => {
    clearTypingAnimation()
    clearTimeout(historySearchTimer)
})


const updateHighlightedMessageFromUrl = () => {
    const params = new URLSearchParams(window.location.search)

    highlightedMessageId.value = params.get('message')
    highlightedSearchTerm.value = params.get('q') || ''
}

const scrollToHighlightedMessage = async () => {
    updateHighlightedMessageFromUrl()

    if (!highlightedMessageId.value) return

    await nextTick()

    const element = document.getElementById(`ai-message-${highlightedMessageId.value}`)

    if (!element) return

    element.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    })
}

const copiedShareVisibility = ref(null)
const sharingVisibility = ref(null)
const revokingShareId = ref(null)

const activeShares = computed(() => {
    return props.activeConversation?.shares ?? []
})

const publicShare = computed(() => {
    return activeShares.value.find(share => share.visibility === 'public')
})

const restrictedShare = computed(() => {
    return activeShares.value.find(share => share.visibility === 'restricted')
})

const copyText = async (text) => {
    if (!text) return

    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text)
        return
    }

    const textarea = document.createElement('textarea')

    textarea.value = text
    textarea.setAttribute('readonly', '')
    textarea.style.position = 'fixed'
    textarea.style.left = '-9999px'
    textarea.style.top = '-9999px'

    document.body.appendChild(textarea)

    textarea.select()
    document.execCommand('copy')

    document.body.removeChild(textarea)
}

const createShareLink = (
    visibility,
    regenerate = false
) => {
    if (
        !props.activeConversation
        || sharingVisibility.value
    ) {
        return
    }

    if (regenerate) {
        const confirmed = window.confirm(
            'Ao gerar um novo link, o link anterior deixa imediatamente de funcionar. Queres continuar?'
        )

        if (!confirmed) return
    }

    sharingVisibility.value = visibility
    streamError.value = null

    router.post(
        route(
            'ai-chat.shares.store',
            props.activeConversation.id
        ),
        {
            visibility,
        },
        {
            preserveScroll: true,

            onError: () => {
                streamError.value = regenerate
                    ? 'Não foi possível regenerar o link de partilha.'
                    : 'Não foi possível criar o link de partilha.'
            },

            onFinish: () => {
                sharingVisibility.value = null
            },
        }
    )
}

const revokeShareLink = (share) => {
    if (!share || revokingShareId.value) return

    revokingShareId.value = share.id
    streamError.value = null

    router.delete(route('ai-chat.shares.destroy', share.id), {
        preserveScroll: true,
        onError: () => {
            streamError.value = 'Não foi possível revogar o link de partilha.'
        },
        onFinish: () => {
            revokingShareId.value = null
        },
    })
}

const copyShareLink = async (share) => {
    try {
        await copyText(share.url)

        copiedShareVisibility.value = share.visibility

        window.setTimeout(() => {
            if (copiedShareVisibility.value === share.visibility) {
                copiedShareVisibility.value = null
            }
        }, 1600)
    } catch {
        streamError.value = 'Não foi possível copiar o link.'
    }
}

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

                <div class="chat-scroll min-h-0 flex-1 overflow-y-auto pr-2 pt-2">
                    <div v-if="isSearchingHistory" class="space-y-2">
                        <div
                            v-if="historySearchLoading"
                            class="rounded-2xl border border-base-300 bg-base-200/50 p-5 text-center text-sm opacity-70"
                        >
                            <span class="loading loading-spinner loading-sm"></span>
                            <p class="mt-2">A pesquisar no histórico...</p>
                        </div>

                        <div
                            v-else-if="historySearchError"
                            class="rounded-2xl border border-error/30 bg-error/10 p-4 text-sm text-error"
                        >
                            {{ historySearchError }}
                        </div>

                        <button
                            v-for="result in historySearchResults"
                            :key="`${result.conversation_id}-${result.message_id || 'title'}`"
                            type="button"
                            class="block w-full rounded-2xl border border-base-300 bg-base-200/60 p-4 text-left transition hover:-translate-y-0.5 hover:border-primary/30 hover:bg-base-200 hover:shadow-lg"
                            @click="openSearchResult(result)"
                        >
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-base-100 text-xs font-black">
                                    {{ result.role === 'assistant' ? 'AI' : (result.role === 'user' ? 'TU' : '💬') }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-sm font-black">
                                            {{ result.title }}
                                        </p>

                                        <span class="badge badge-ghost badge-sm shrink-0 rounded-lg">
                                            {{ resultRoleLabel(result.role) }}
                                        </span>
                                    </div>

                                    <p
                                        class="mt-2 line-clamp-3 text-xs leading-5 opacity-70"
                                        v-html="renderSearchExcerpt(result.excerpt)"
                                    ></p>

                                    <p class="mt-2 truncate text-[11px] opacity-45">
                                        {{ result.model_id || 'sem modelo' }}
                                    </p>
                                </div>
                            </div>
                        </button>

                        <div
                            v-if="!historySearchLoading && !historySearchResults.length && !historySearchError"
                            class="rounded-2xl border border-dashed border-base-300 bg-base-200/50 p-6 text-center text-sm opacity-60"
                        >
                            Nenhum resultado encontrado.
                        </div>
                    </div>
                    <div v-else>
                        <div
                            v-if="pinnedConversations.length"
                            class="mb-5"
                        >
                            <p class="mb-2 px-1 text-[11px] font-black uppercase tracking-[0.18em] opacity-50">
                                Fixadas
                            </p>

                            <div class="space-y-2">
                                <Link
                                    v-for="conversation in pinnedConversations"
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
                                            <div class="flex items-center gap-2">
                                                
                                                <svg class="h-3.5 w-3.5 shrink-0 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16 12V4H17V2H7V4H8V12L6 14V16H11.2V22H12.8V16H18V14L16 12ZM8.8 14L10 12.8V4H14V12.8L15.2 14H8.8Z" fill="currentColor"/>
                                                </svg>

                                                <p class="truncate text-sm font-bold">
                                                    {{ conversation.title }}
                                                </p>
                                            </div>

                                            <p class="mt-1 truncate text-xs opacity-60">
                                                {{ conversation.model_id || 'sem modelo' }}
                                            </p>

                                            <p class="mt-2 text-[11px] opacity-50">
                                                {{ formatDate(conversation.updated_at) }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs rounded-lg opacity-0 transition group-hover:opacity-100"
                                            title="Desafixar"
                                            @click.prevent.stop="togglePinConversation(conversation)"
                                        >
                                            
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8 6.2V4H7V2H17V4H16V12L18 14V16H17.8L14 12.2V4H10V8.2L8 6.2ZM20 20.7L18.7 22L12.8 16.1V22H11.2V16H6V14L8 12V11.3L2 5.3L3.3 4L20 20.7ZM8.8 14H10.6L9.7 13.1L8.8 14Z" fill="currentColor"/>
                                            </svg>

                                        </button>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p
                            v-if="unpinnedConversations.length"
                            class="mb-2 px-1 text-[11px] font-black uppercase tracking-[0.18em] opacity-50"
                        >
                            Recentes
                        </p>

                        <div class="space-y-2">
                            <Link
                                v-for="conversation in unpinnedConversations"
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

                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-xs rounded-lg opacity-0 transition group-hover:opacity-100"
                                        title="Fixar"
                                        @click.prevent.stop="togglePinConversation(conversation)"
                                    >

                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 12V4H17V2H7V4H8V12L6 14V16H11.2V22H12.8V16H18V14L16 12ZM8.8 14L10 12.8V4H14V12.8L15.2 14H8.8Z" fill="currentColor"/>
                                        </svg>

                                    </button>
                                </div>
                            </Link>
                        </div>
                    </div>

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
                <div class="relative z-[80] shrink-0 overflow-visible border-b border-base-300/80 bg-base-100/80 p-4 backdrop-blur-xl">
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
                                class="btn btn-outline btn-sm gap-2 rounded-xl"
                                :class="activeConversation.pinned_at ? 'border-primary/40 text-primary' : ''"
                                :disabled="streaming"
                                @click="togglePinConversation(activeConversation)"
                            >

                                <svg v-if="activeConversation.pinned_at" class="v-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 12V4H17V2H7V4H8V12L6 14V16H11.2V22H12.8V16H18V14L16 12ZM8.8 14L10 12.8V4H14V12.8L15.2 14H8.8Z" fill="currentColor"/>
                                </svg>

                                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 12V4H17V2H7V4H8V12L6 14V16H11.2V22H12.8V16H18V14L16 12ZM8.8 14L10 12.8V4H14V12.8L15.2 14H8.8Z" fill="currentColor"/>
                                </svg>

                                {{ activeConversation.pinned_at ? 'Fixada' : 'Fixar' }}
                            </button>

                            <div
                                v-if="activeConversation"
                                class="dropdown dropdown-end relative"
                            >
                                <button
                                    tabindex="0"
                                    type="button"
                                    class="btn btn-outline btn-sm gap-2 rounded-xl"
                                    :disabled="streaming"
                                >

                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8 17V15H16V17H8ZM16 10L12 14L8 10H10.5V7H13.5V10H16ZM5 3H19C20.11 3 21 3.9 21 5V19C21 20.11 20.11 21 19 21H5C3.9 21 3 20.11 3 19V5C3 3.9 3.9 3 5 3ZM5 5V19H19V5H5Z" fill="currentColor"/>
                                    </svg>

                                    Exportar
                                </button>

                                <ul
                                    tabindex="0"
                                    class="dropdown-content z-[9999] mt-2 w-52 rounded-2xl border border-base-300 bg-base-100 p-2 shadow-2xl"
                                >
                                    <li>
                                        <a
                                            :href="route('ai-chat.export.markdown', activeConversation.id)"
                                            class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-base-200"
                                        >
                                            <span>MD</span>
                                            Markdown
                                        </a>
                                    </li>

                                    <li>
                                        <a
                                            :href="route('ai-chat.export.pdf', activeConversation.id)"
                                            class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-base-200"
                                        >
                                            <span>PDF</span>
                                            PDF
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            
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
                    class="chat-scroll relative z-0 min-h-0 flex-1 overflow-y-auto scroll-smooth px-4 py-4 md:px-6"
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
                                :id="message.id ? `ai-message-${message.id}` : null"
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
                                    class="w-fit overflow-x-auto rounded-[1.5rem] border px-5 py-4 shadow-sm transition"
                                    :class="[
                                        message.role === 'user'
                                            ? 'max-w-[82%] border-primary/30 bg-primary text-primary-content shadow-primary/10'
                                            : 'max-w-[96%] border-base-300 bg-base-100/85',
                                        String(highlightedMessageId) === String(message.id)
                                            ? 'ring-2 ring-primary/50 ring-offset-4 ring-offset-base-100'
                                            : ''
                                    ]"
                                >
                                    <div class="mb-2 flex items-center justify-between gap-4 text-xs">
                                        <span class="font-black opacity-70">
                                            {{ roleLabel(message.role) }}
                                        </span>

                                        <div class="flex items-center gap-2">

                                            <button
                                                v-if="isPersistedMessage(message) && !message.streaming"
                                                type="button"
                                                class="btn btn-ghost btn-xs gap-1 rounded-lg px-2 transition"
                                                :class="messageComments(message).length
                                                    ? 'text-primary opacity-100'
                                                    : 'opacity-70 md:opacity-0 md:group-hover:opacity-100'"
                                                :title="messageComments(message).length
                                                    ? `${messageComments(message).length} comentários`
                                                    : 'Adicionar comentário'"
                                                @click="openCommentCreate(message)"
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
                                                        d="M8 10h8M8 14h5m8-2a9 9 0 01-9 9 9.7 9.7 0 01-4-.85L3 21l.85-5A9 9 0 1121 12z"
                                                    />
                                                </svg>

                                                <span v-if="messageComments(message).length">
                                                    {{ messageComments(message).length }}
                                                </span>
                                            </button>

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
                                            v-html="renderAssistantMessage(message)"
                                        ></div>

                                        <div
                                            v-else-if="message.content"
                                            class="whitespace-pre-line"
                                            v-html="renderUserMessage(message)"
                                        ></div>

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

                                    <div
                                        v-if="
                                            messageComments(message).length
                                            || commentEditorMessageId === message.id
                                        "
                                        class="mt-4 border-t border-current/15 pt-3"
                                    >
                                        <div
                                            v-if="messageComments(message).length"
                                            class="space-y-2"
                                        >
                                            <div
                                                v-for="comment in messageComments(message)"
                                                :key="comment.id"
                                                class="rounded-2xl border border-base-300 bg-base-200/90 p-3 text-base-content"
                                            >
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="mb-1 flex items-center gap-2">
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                class="h-4 w-4 shrink-0 text-primary"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                stroke="currentColor"
                                                                stroke-width="2"
                                                            >
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    d="M8 10h8M8 14h5m8-2a9 9 0 01-9 9 9.7 9.7 0 01-4-.85L3 21l.85-5A9 9 0 1121 12z"
                                                                />
                                                            </svg>

                                                            <span class="text-xs font-black">
                                                                Nota privada
                                                            </span>
                                                        </div>

                                                        <p class="whitespace-pre-line text-sm leading-6">
                                                            {{ comment.content }}
                                                        </p>

                                                        <p class="mt-2 text-[11px] opacity-50">
                                                            {{ formatDate(comment.created_at) }}
                                                            ·
                                                            {{ formatTime(comment.created_at) }}

                                                            <span
                                                                v-if="comment.updated_at !== comment.created_at"
                                                            >
                                                                · editado
                                                            </span>
                                                        </p>
                                                    </div>

                                                    <div class="flex shrink-0 items-center gap-1">
                                                        <button
                                                            type="button"
                                                            class="btn btn-ghost btn-xs rounded-lg"
                                                            :disabled="
                                                                savingComment
                                                                || deletingCommentId === comment.id
                                                            "
                                                            title="Editar comentário"
                                                            @click="openCommentEdit(message, comment)"
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
                                                                    d="M16.862 3.487a2.25 2.25 0 113.182 3.182L9.75 16.963 5.5 18.5l1.537-4.25L16.862 3.487z"
                                                                />
                                                            </svg>
                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="btn btn-ghost btn-xs rounded-lg text-error"
                                                            :disabled="
                                                                savingComment
                                                                || deletingCommentId === comment.id
                                                            "
                                                            title="Eliminar comentário"
                                                            @click="removeMessageComment(comment)"
                                                        >
                                                            <span
                                                                v-if="deletingCommentId === comment.id"
                                                                class="loading loading-spinner loading-xs"
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
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <form
                                            v-if="commentEditorMessageId === message.id"
                                            class="mt-3 rounded-2xl border border-primary/25 bg-base-100 p-3 text-base-content shadow-sm"
                                            @submit.prevent="saveMessageComment(message)"
                                        >
                                            <div class="mb-2 flex items-center justify-between gap-3">
                                                <p class="text-xs font-black uppercase tracking-[0.15em] opacity-60">
                                                    {{ editingCommentId
                                                        ? 'Editar comentário'
                                                        : 'Novo comentário' }}
                                                </p>

                                                <button
                                                    type="button"
                                                    class="btn btn-ghost btn-xs rounded-lg"
                                                    :disabled="savingComment"
                                                    @click="closeCommentEditor"
                                                >
                                                    ✕
                                                </button>
                                            </div>

                                            <textarea
                                                v-model="commentForm.content"
                                                rows="3"
                                                maxlength="500"
                                                class="textarea textarea-bordered chat-scroll w-full resize-none rounded-xl text-sm"
                                                placeholder="Escreve uma nota privada sobre esta mensagem..."
                                                :disabled="savingComment"
                                            ></textarea>

                                            <p
                                                v-if="commentErrors.content"
                                                class="mt-1 text-xs text-error"
                                            >
                                                {{ Array.isArray(commentErrors.content)
                                                    ? commentErrors.content[0]
                                                    : commentErrors.content }}
                                            </p>

                                            <div class="mt-2 flex items-center justify-between gap-3">
                                                <span class="text-xs opacity-50">
                                                    {{ commentForm.content.length }}/500
                                                </span>

                                                <div class="flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        class="btn btn-ghost btn-xs rounded-xl"
                                                        :disabled="savingComment"
                                                        @click="closeCommentEditor"
                                                    >
                                                        Cancelar
                                                    </button>

                                                    <button
                                                        type="submit"
                                                        class="btn btn-primary btn-xs rounded-xl"
                                                        :disabled="
                                                            savingComment
                                                            || !commentForm.content.trim()
                                                        "
                                                    >
                                                        <span
                                                            v-if="savingComment"
                                                            class="loading loading-spinner loading-xs"
                                                        ></span>

                                                        {{ editingCommentId
                                                            ? 'Guardar'
                                                            : 'Adicionar' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
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

                            <input
                                ref="attachmentInput"
                                type="file"
                                class="hidden"
                                accept=".txt,.md,.pdf,.js,.ts,.php,.py,.html,.css,.json,.xml,.csv"
                                :disabled="streaming"
                                @change="handleAttachmentChange"
                            />

                            <div
                                v-if="attachmentFile"
                                class="mb-2 flex items-center justify-between gap-3 rounded-2xl border border-primary/20 bg-primary/10 px-3 py-2 text-sm"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-bold">
                                        📎 {{ attachmentFile.name }}
                                    </p>

                                    <p class="text-xs opacity-60">
                                        {{ formatFileSize(attachmentFile.size) }}
                                    </p>

                                    <p
                                        v-if="attachmentInspecting"
                                        class="mt-1 flex items-center gap-2 text-xs opacity-60"
                                    >
                                        <span class="loading loading-spinner loading-xs"></span>
                                        A analisar conteúdo...
                                    </p>

                                    <p
                                        v-else-if="attachmentInspection"
                                        class="mt-1 text-xs opacity-60"
                                    >
                                        Aproximadamente
                                        {{ Number(attachmentInspection.estimated_tokens).toLocaleString('pt-PT') }}
                                        tokens
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="btn btn-ghost btn-xs rounded-lg"
                                    :disabled="streaming"
                                    @click="clearAttachment"
                                >
                                    Remover
                                </button>
                            </div>

                            <div
                                v-if="attachmentContextInfo?.warning"
                                class="alert alert-warning mb-2 rounded-2xl py-3 text-sm"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 shrink-0"
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

                                <div>
                                    <p class="font-bold">
                                        Atenção ao limite de contexto
                                    </p>

                                    <p>
                                        {{ attachmentContextInfo.warning }}
                                    </p>

                                    <p
                                        v-if="attachmentContextInfo.modelContextLength"
                                        class="mt-1 text-xs opacity-70"
                                    >
                                        Total estimado:
                                        {{ attachmentContextInfo.estimatedTotalTokens.toLocaleString('pt-PT') }}
                                        tokens · Limite do modelo:
                                        {{ attachmentContextInfo.modelContextLength.toLocaleString('pt-PT') }}
                                        tokens
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="attachmentError"
                                class="alert alert-error mb-2 rounded-2xl py-2 text-sm"
                            >
                                {{ attachmentError }}
                            </div>

                            <textarea
                                ref="messageInput"
                                v-model="form.message"
                                rows="1"
                                maxlength="10000"
                                class="chat-scroll textarea max-h-28 min-h-11 w-full resize-none overflow-y-auto border-0 bg-transparent py-2 text-base leading-6 outline-none focus:outline-none"
                                placeholder="Escreve a tua mensagem..."
                                :disabled="streaming || !openRouterConfigured"
                                @keydown.enter.exact.prevent="submit"
                            ></textarea>

                            <div class="flex items-center justify-between gap-2 border-t border-base-300 pt-2">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-sm rounded-xl"
                                        :disabled="streaming"
                                        @click="attachmentInput?.click()"
                                    >
                                        📎 Anexar
                                    </button>

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
                            v-if="streamError || formErrors.message || formErrors.model_id || formErrors.temperature || formErrors.max_tokens || formErrors.attachment"
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

                                <p v-if="formErrors.attachment">
                                    {{ Array.isArray(formErrors.attachment) ? formErrors.attachment[0] : formErrors.attachment }}
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

                <div class="mb-5 rounded-[1.5rem] border border-base-300 bg-base-200/60 p-4">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-black uppercase tracking-[0.2em] opacity-50">
                                Prompt Library
                            </h2>

                            <p class="mt-1 text-xs opacity-60">
                                Prompts privados reutilizáveis
                            </p>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary btn-xs rounded-xl"
                            @click="openPromptCreate"
                        >
                            + Novo
                        </button>
                    </div>

                    <input
                        v-model="promptSearch"
                        type="text"
                        class="input input-bordered input-sm w-full rounded-2xl bg-base-100"
                        placeholder="Pesquisar por nome..."
                    />

                    <div
                        v-if="showPromptForm"
                        class="mt-4 rounded-2xl border border-base-300 bg-base-100 p-3"
                    >
                        <div class="space-y-3">
                            <div>
                                <label class="mb-1 block text-xs font-bold opacity-60">
                                    Nome
                                </label>

                                <input
                                    v-model="promptForm.name"
                                    type="text"
                                    maxlength="80"
                                    class="input input-bordered input-sm w-full rounded-xl"
                                    placeholder="Ex: Recomendação de livros"
                                />

                                <p
                                    v-if="promptErrors.name"
                                    class="mt-1 text-xs text-error"
                                >
                                    {{ Array.isArray(promptErrors.name) ? promptErrors.name[0] : promptErrors.name }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold opacity-60">
                                    Texto do prompt
                                </label>

                                <textarea
                                    v-model="promptForm.content"
                                    maxlength="2000"
                                    rows="5"
                                    class="textarea textarea-bordered chat-scroll w-full resize-none rounded-xl text-sm"
                                    placeholder="Escreve aqui o prompt reutilizável..."
                                ></textarea>

                                <div class="mt-1 flex items-center justify-between text-xs">
                                    <p
                                        v-if="promptErrors.content"
                                        class="text-error"
                                    >
                                        {{ Array.isArray(promptErrors.content) ? promptErrors.content[0] : promptErrors.content }}
                                    </p>

                                    <p class="ml-auto opacity-50">
                                        {{ promptForm.content.length }}/2000
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-xs rounded-xl"
                                    :disabled="savingPrompt"
                                    @click="closePromptForm"
                                >
                                    Cancelar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-primary btn-xs rounded-xl"
                                    :disabled="savingPrompt"
                                    @click="savePrompt"
                                >
                                    <span
                                        v-if="savingPrompt"
                                        class="loading loading-spinner loading-xs"
                                    ></span>

                                    {{ editingPromptId ? 'Guardar' : 'Criar' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="chat-scroll mt-4 max-h-72 space-y-2 overflow-y-auto pr-1">
                        <div
                            v-for="prompt in filteredPrompts"
                            :key="prompt.id"
                            class="rounded-2xl border border-base-300 bg-base-100 p-3"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black">
                                        {{ prompt.name }}
                                    </p>

                                    <p class="mt-1 line-clamp-2 text-xs opacity-60">
                                        {{ prompt.content }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-xs rounded-lg"
                                    @click="insertPrompt(prompt)"
                                >
                                    Inserir
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-ghost btn-xs rounded-lg"
                                    @click="openPromptEdit(prompt)"
                                >
                                    Editar
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-ghost btn-xs rounded-lg text-error"
                                    :disabled="deletingPromptId === prompt.id"
                                    @click="removePrompt(prompt)"
                                >
                                    <span
                                        v-if="deletingPromptId === prompt.id"
                                        class="loading loading-spinner loading-xs"
                                    ></span>

                                    <span v-else>
                                        Eliminar
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="!filteredPrompts.length"
                            class="rounded-2xl border border-dashed border-base-300 bg-base-100 p-5 text-center text-sm opacity-60"
                        >
                            Nenhum prompt encontrado.
                        </div>
                    </div>
                </div>

                






                <div
                    v-if="activeConversation"
                    class="mb-5 rounded-[1.5rem] border border-base-300 bg-base-200/60 p-4"
                >
                    <div class="mb-4">
                        <h2 class="text-sm font-black uppercase tracking-[0.2em] opacity-50">
                            Partilha
                        </h2>

                        <p class="mt-1 text-xs opacity-60">
                            Links só de leitura para esta conversa
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div class="rounded-2xl border border-base-300 bg-base-100 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black">
                                        Link público
                                    </p>

                                    <p class="mt-1 text-xs opacity-60">
                                        Qualquer pessoa com o link pode ver.
                                    </p>
                                </div>

                                <div class="badge badge-outline rounded-xl">
                                    Público
                                </div>
                            </div>

                            <div v-if="publicShare" class="mt-3 space-y-2">
                                <input
                                    :value="publicShare.url"
                                    type="text"
                                    readonly
                                    class="input input-bordered input-sm w-full rounded-xl bg-base-200 text-xs"
                                />

                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-xs rounded-xl"
                                        @click="copyShareLink(publicShare)"
                                    >
                                        {{ copiedShareVisibility === 'public' ? 'Copiado' : 'Copiar' }}
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline btn-xs rounded-xl"
                                        :disabled="sharingVisibility === 'public'"
                                        @click="createShareLink('public', true)"
                                    >
                                        <span
                                            v-if="sharingVisibility === 'public'"
                                            class="loading loading-spinner loading-xs"
                                        ></span>

                                        {{ sharingVisibility === 'public'
                                            ? 'A gerar...'
                                            : 'Regenerar' }}
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-error btn-outline btn-xs rounded-xl"
                                        :disabled="revokingShareId === publicShare.id"
                                        @click="revokeShareLink(publicShare)"
                                    >
                                        <span
                                            v-if="revokingShareId === publicShare.id"
                                            class="loading loading-spinner loading-xs"
                                        ></span>

                                        Revogar
                                    </button>
                                </div>
                            </div>

                            <button
                                v-else
                                type="button"
                                class="btn btn-primary btn-xs mt-3 rounded-xl"
                                :disabled="sharingVisibility === 'public'"
                                @click="createShareLink('public')"
                            >
                                <span
                                    v-if="sharingVisibility === 'public'"
                                    class="loading loading-spinner loading-xs"
                                ></span>

                                Criar link público
                            </button>
                        </div>

                        <div class="rounded-2xl border border-base-300 bg-base-100 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black">
                                        Link restrito
                                    </p>

                                    <p class="mt-1 text-xs opacity-60">
                                        Apenas utilizadores autenticados podem ver.
                                    </p>
                                </div>

                                <div class="badge badge-outline rounded-xl">
                                    Restrito
                                </div>
                            </div>

                            <div v-if="restrictedShare" class="mt-3 space-y-2">
                                <input
                                    :value="restrictedShare.url"
                                    type="text"
                                    readonly
                                    class="input input-bordered input-sm w-full rounded-xl bg-base-200 text-xs"
                                />

                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-xs rounded-xl"
                                        @click="copyShareLink(restrictedShare)"
                                    >
                                        {{ copiedShareVisibility === 'restricted' ? 'Copiado' : 'Copiar' }}
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-outline btn-xs rounded-xl"
                                        :disabled="sharingVisibility === 'restricted'"
                                        @click="createShareLink('restricted', true)"
                                    >
                                        <span
                                            v-if="sharingVisibility === 'restricted'"
                                            class="loading loading-spinner loading-xs"
                                        ></span>

                                        {{ sharingVisibility === 'restricted'
                                            ? 'A gerar...'
                                            : 'Regenerar' }}
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-error btn-outline btn-xs rounded-xl"
                                        :disabled="revokingShareId === restrictedShare.id"
                                        @click="revokeShareLink(restrictedShare)"
                                    >
                                        <span
                                            v-if="revokingShareId === restrictedShare.id"
                                            class="loading loading-spinner loading-xs"
                                        ></span>

                                        Revogar
                                    </button>
                                </div>
                            </div>

                            <button
                                v-else
                                type="button"
                                class="btn btn-primary btn-xs mt-3 rounded-xl"
                                :disabled="sharingVisibility === 'restricted'"
                                @click="createShareLink('restricted')"
                            >
                                <span
                                    v-if="sharingVisibility === 'restricted'"
                                    class="loading loading-spinner loading-xs"
                                ></span>

                                Criar link restrito
                            </button>
                        </div>
                    </div>
                </div>

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

@keyframes searchPulse {
    0% {
        box-shadow: 0 0 0 0 hsl(var(--p) / 0.45);
    }

    100% {
        box-shadow: 0 0 0 14px transparent;
    }
}

.search-highlight {
    animation: searchPulse 900ms ease-out 2;
}

:deep(mark.ai-search-mark) {
    border-radius: 0.45rem;
    background: hsl(var(--wa) / 0.45);
    color: inherit;
    padding: 0.05rem 0.25rem;
    font-weight: 900;
    text-decoration: underline;
    text-decoration-thickness: 2px;
    text-underline-offset: 3px;
    box-shadow: 0 0 0 1px hsl(var(--wa) / 0.55);
}

</style>