<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">

    <style>
        @page {
            margin: 32px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 13px;
            line-height: 1.55;
        }

        h1 {
            font-size: 26px;
            margin-bottom: 8px;
        }

        h2 {
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 8px;
        }

        .meta {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .meta p {
            margin: 2px 0;
        }

        .message {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .message-user {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .message-assistant {
            background: #f9fafb;
        }

        .message-header {
            margin-bottom: 12px;
            color: #374151;
            font-size: 12px;
        }

        .message-header strong {
            color: #111827;
        }

        .content h1,
        .content h2,
        .content h3 {
            margin-top: 14px;
            margin-bottom: 8px;
        }

        .content p {
            margin: 0 0 10px;
        }

        .content ul,
        .content ol {
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .content code {
            background: #e5e7eb;
            padding: 2px 4px;
            border-radius: 4px;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 12px;
        }

        .content pre {
            background: #111827;
            color: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            overflow-wrap: break-word;
            white-space: pre-wrap;
        }

        .content {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .content blockquote {
            margin: 12px 0;
            padding-left: 12px;
            border-left: 4px solid #9ca3af;
            color: #4b5563;
        }

        .content a {
            color: #2563eb;
            text-decoration: underline;
        }

        .content hr {
            border: 0;
            border-top: 1px solid #d1d5db;
            margin: 16px 0;
        }

        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 11px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            vertical-align: top;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        th {
            background: #f3f4f6;
        }

        .footer {
            margin-top: 28px;
            color: #9ca3af;
            font-size: 11px;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $markdownConverter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    @endphp


    <h1>{{ $conversation->title ?: 'Conversa sem título' }}</h1>

    <div class="meta">
        <p><strong>Data de criação:</strong> {{ optional($conversation->created_at)->format('d/m/Y H:i') }}</p>
        <p><strong>Modelo principal:</strong> {{ $conversation->model_id ?: 'Sem modelo' }}</p>
        <p><strong>Temperature:</strong> {{ $conversation->temperature }}</p>
        <p><strong>Max tokens:</strong> {{ $conversation->max_tokens }}</p>
    </div>

    @foreach ($conversation->messages as $message)
        <div class="message {{ $message->role === 'user' ? 'message-user' : 'message-assistant' }}">
            <h2>{{ $message->role === 'user' ? 'Utilizador' : 'IA' }}</h2>

            <div class="message-header">
                <p>
                    <strong>Data/Hora:</strong>
                    {{ optional($message->created_at)->format('d/m/Y H:i') }}
                </p>

                <p>
                    <strong>Modelo:</strong>
                    {{ $message->model_id ?: $conversation->model_id ?: 'Sem modelo' }}
                </p>
            </div>

            <div class="content">
                @if ($message->role === 'assistant')
                    {!! $markdownConverter->convert($message->content ?? '')->getContent() !!}
                @else
                    {!! nl2br(e($message->content ?? '')) !!}
                @endif
            </div>
        </div>
    @endforeach

    <div class="footer">
        Exportado pela Biblioteca · AI Chat
    </div>
</body>
</html>