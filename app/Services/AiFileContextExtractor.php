<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser;

class AiFileContextExtractor
{
    public const ALLOWED_EXTENSIONS = [
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
    ];

    /**
     * Limite máximo de texto enviado ao modelo.
     *
     * Cerca de 120 000 caracteres correspondem aproximadamente
     * a 30 000 tokens, embora isto varie conforme o conteúdo.
     */
    public const MAX_CONTEXT_CHARACTERS = 120000;

    public function extract(UploadedFile $file): array
    {
        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $fileName = $file->getClientOriginalName();

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \RuntimeException(
                'Tipo de ficheiro não permitido.'
            );
        }

        $rawText = $extension === 'pdf'
            ? $this->extractPdfText($file)
            : $this->extractPlainText($file);

        $cleanText = $this->cleanText($rawText);

        if ($cleanText === '') {
            throw new \RuntimeException(
                'Não foi possível extrair texto útil do ficheiro.'
            );
        }

        $originalCharacters = mb_strlen($cleanText);
        $originalEstimatedTokens = $this->estimateTokens($cleanText);

        $wasTruncated = $originalCharacters
            > self::MAX_CONTEXT_CHARACTERS;

        $content = $wasTruncated
            ? mb_substr(
                $cleanText,
                0,
                self::MAX_CONTEXT_CHARACTERS
            ) . "\n\n[Conteúdo truncado por limite de tamanho]"
            : $cleanText;

        return [
            'file_name' => $fileName,
            'extension' => $extension,

            // Conteúdo que será efetivamente enviado ao modelo.
            'content' => $content,

            'context' => "Conteúdo do ficheiro: {$fileName}\n\n{$content}",

            // Estatísticas.
            'characters' => mb_strlen($content),
            'estimated_tokens' => $this->estimateTokens($content),

            'original_characters' => $originalCharacters,
            'original_estimated_tokens' => $originalEstimatedTokens,

            'was_truncated' => $wasTruncated,
        ];
    }

    public function estimateTokens(string $text): int
    {
        $characters = mb_strlen($text);

        if ($characters === 0) {
            return 0;
        }

        /*
         * Aproximação comum:
         * cerca de quatro caracteres por token.
         *
         * Não é uma contagem exata, mas é suficiente para
         * apresentar um aviso antes do envio.
         */
        return (int) ceil($characters / 4);
    }

    private function extractPlainText(UploadedFile $file): string
    {
        return file_get_contents($file->getRealPath()) ?: '';
    }

    private function extractPdfText(UploadedFile $file): string
    {
        $parser = new Parser();

        $pdf = $parser->parseFile(
            $file->getRealPath()
        );

        return $pdf->getText() ?: '';
    }

    private function cleanText(string $text): string
    {
        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        $text = preg_replace(
            "/\n{4,}/",
            "\n\n\n",
            $text
        ) ?? $text;

        return trim($text);
    }
}