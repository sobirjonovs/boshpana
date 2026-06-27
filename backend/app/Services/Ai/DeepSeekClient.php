<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * DeepSeek chat client (OpenAI-compatible Chat Completions API).
 *
 * Endpoint: {base_url}/chat/completions, Bearer auth, model "deepseek-chat"
 * (DeepSeek-V3) by default. Structured output uses DeepSeek's JSON mode
 * (response_format: {type: "json_object"}) — the schema is passed as an
 * instruction (DeepSeek validates JSON-ness, not the schema itself).
 *
 * NOTE: DeepSeek is text-only — image content blocks are flattened to a short
 * "[rasm: url]" note, so listing analysis runs on text only for now.
 */
class DeepSeekClient implements AiClient
{
    public function __construct(private readonly array $config)
    {
    }

    public static function fromConfig(): self
    {
        return new self(config('boshpana.ai.deepseek'));
    }

    public function enabled(): bool
    {
        return ! empty($this->config['api_key']);
    }

    public function model(): string
    {
        return $this->config['model'];
    }

    public function text(array $messages, ?string $system = null, array $options = []): string
    {
        return $this->extractText($this->chat($messages, $system, $options));
    }

    public function structured(array $messages, array $schema, ?string $system = null, array $options = []): array
    {
        $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $system = trim(($system ? $system."\n\n" : '')
            ."Respond with a SINGLE valid JSON object that conforms to this JSON schema. "
            ."Output ONLY the JSON — no markdown fences, no commentary.\nSchema:\n".$schemaJson);

        $options['response_format'] = ['type' => 'json_object'];

        $text = trim($this->extractText($this->chat($messages, $system, $options)));

        $decoded = json_decode($text, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
        }
        if (! is_array($decoded)) {
            throw new RuntimeException('DeepSeek returned non-JSON structured output: '.mb_substr($text, 0, 200));
        }

        return $decoded;
    }

    /** Anthropic-compatible block builders so callers stay provider-neutral. */
    public static function imageBlock(string $url): array
    {
        return ['type' => 'image', 'source' => ['type' => 'url', 'url' => $url]];
    }

    public static function textBlock(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    private function chat(array $messages, ?string $system, array $options): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('DeepSeek API key is not configured (boshpana.ai.deepseek.api_key).');
        }

        $payload = [
            'model' => $this->config['model'],
            'messages' => $this->buildMessages($messages, $system),
            'max_tokens' => $this->config['max_tokens'],
            'temperature' => $options['temperature'] ?? ($this->config['temperature'] ?? 0.4),
            'stream' => false,
        ];
        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'])
            ->retry(2, 500, throw: false)
            ->post(rtrim($this->config['base_url'], '/').'/chat/completions', $payload);

        if ($response->failed()) {
            Log::warning('DeepSeek request failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException('DeepSeek request failed: HTTP '.$response->status());
        }

        return $response->json() ?? [];
    }

    private function buildMessages(array $messages, ?string $system): array
    {
        $out = [];
        if ($system) {
            $out[] = ['role' => 'system', 'content' => $system];
        }
        foreach ($messages as $m) {
            $out[] = [
                'role' => $this->role($m['role'] ?? 'user'),
                'content' => $this->flatten($m['content'] ?? ''),
            ];
        }

        return $out;
    }

    private function role(string $role): string
    {
        return match ($role) {
            'assistant', 'ai' => 'assistant',
            'system' => 'system',
            default => 'user',
        };
    }

    private function flatten(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $block) {
                if (is_array($block)) {
                    $type = $block['type'] ?? null;
                    if ($type === 'text') {
                        $parts[] = $block['text'] ?? '';
                    } elseif ($type === 'image') {
                        $parts[] = '[rasm: '.($block['source']['url'] ?? '').']';
                    }
                } elseif (is_string($block)) {
                    $parts[] = $block;
                }
            }

            return trim(implode("\n", array_filter($parts, fn ($p) => $p !== '')));
        }

        return (string) $content;
    }

    private function extractText(array $response): string
    {
        return $response['choices'][0]['message']['content'] ?? '';
    }
}
