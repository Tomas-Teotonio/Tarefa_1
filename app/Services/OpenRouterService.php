<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    public function isConfigured(): bool
    {
        return filled(config('services.openrouter.key'));
    }

    public function models(): array
    {
        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'error' => 'A chave da API OpenRouter não está configurada.',
                'models' => [],
            ];
        }

        try {
            $response = Http::timeout(20)
                ->withToken(config('services.openrouter.key'))
                ->acceptJson()
                ->get('https://openrouter.ai/api/v1/models');

            if (!$response->successful()) {
                Log::warning('OpenRouter models error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'ok' => false,
                    'error' => 'Erro ao carregar modelos do OpenRouter.',
                    'models' => [],
                ];
            }

            $models = collect($response->json('data') ?? [])
                ->map(function ($model) {
                    return [
                        'id' => $model['id'] ?? null,
                        'name' => $model['name'] ?? $model['id'] ?? 'Sem nome',
                        'context_length' => $model['context_length'] ?? null,
                        'pricing' => $model['pricing'] ?? null,
                    ];
                })
                ->filter(fn ($model) => filled($model['id']))
                ->values()
                ->toArray();

            return [
                'ok' => true,
                'error' => null,
                'models' => $models,
            ];

        } catch (\Throwable $e) {
            Log::error('OpenRouter models exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => 'Erro inesperado ao comunicar com o OpenRouter.',
                'models' => [],
            ];
        }
    }

    public function streamChat(
        array $messages,
        string $modelId,
        float $temperature,
        int $maxTokens,
        callable $onDelta
    ): string {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('A chave da API OpenRouter não está configurada.');
        }

        $payload = [
            'model' => $modelId,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
            'stream' => true,
        ];

        $fullContent = '';
        $buffer = '';
        $streamError = null;

        $headers = [
            'Content-Type: application/json',
            'Accept: text/event-stream',
            'Authorization: Bearer ' . config('services.openrouter.key'),
            'HTTP-Referer: ' . config('services.openrouter.site_url'),
            'X-Title: ' . config('services.openrouter.app_name'),
        ];

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_WRITEFUNCTION => function ($curl, string $chunk) use (&$buffer, &$fullContent, &$streamError, $onDelta) {
                $buffer .= $chunk;

                while (($position = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $position));
                    $buffer = substr($buffer, $position + 1);

                    if ($line === '' || str_starts_with($line, ':')) {
                        continue;
                    }

                    if (!str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $data = trim(substr($line, 5));

                    if ($data === '[DONE]') {
                        continue;
                    }

                    $json = json_decode($data, true);

                    if (!is_array($json)) {
                        continue;
                    }

                    if (isset($json['error'])) {
                        $streamError = $json['error']['message'] ?? 'Erro desconhecido no OpenRouter.';
                        return 0;
                    }

                    $delta = $json['choices'][0]['delta']['content']
                        ?? $json['choices'][0]['message']['content']
                        ?? '';

                    if ($delta !== '') {
                        $fullContent .= $delta;
                        $onDelta($delta);
                    }
                }

                return strlen($chunk);
            },
        ]);

        curl_exec($ch);

        $curlError = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($streamError) {
            throw new \RuntimeException($streamError);
        }

        if ($curlError) {
            throw new \RuntimeException('Erro de ligação ao OpenRouter: ' . $curlError);
        }

        if ($statusCode >= 400) {
            throw new \RuntimeException('OpenRouter respondeu com erro HTTP ' . $statusCode . '.');
        }

        return $fullContent;
    }
}