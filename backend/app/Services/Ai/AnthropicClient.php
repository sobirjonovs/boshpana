<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin wrapper around the Claude Messages API (raw HTTP).
 *
 * The wire format is intentionally hand-rolled (POST /v1/messages with the
 * x-api-key + anthropic-version headers) so the backend has zero SDK-version
 * coupling. Model defaults to claude-opus-4-8.
 *
 * When no API key is configured the client reports `enabled() === false`,
 * letting callers fall back to deterministic heuristics so the whole product
 * still runs offline (simulation mode).
 */
class AnthropicClient implements AiClient
{
    public function __construct(private readonly array $config)
    {
    }

    public static function fromConfig(): self
    {
        return new self(config('boshpana.ai'));
    }

    public function enabled(): bool
    {
        return ! empty($this->config['api_key']);
    }

    public function model(): string
    {
        return $this->config['model'];
    }

    /**
     * Plain text completion. $messages is the Anthropic messages array.
     */
    public function text(array $messages, ?string $system = null, array $options = []): string
    {
        $response = $this->raw($this->payload($messages, $system, $options));

        return $this->extractText($response);
    }

    /**
     * Structured completion constrained to a JSON schema. Returns the decoded
     * associative array. Throws if the model output cannot be decoded.
     *
     * @param  array  $schema  A JSON Schema (object with additionalProperties:false).
     */
    public function structured(array $messages, array $schema, ?string $system = null, array $options = []): array
    {
        $options['output_config'] = [
            'format' => [
                'type' => 'json_schema',
                'schema' => $schema,
            ],
        ];

        $response = $this->raw($this->payload($messages, $system, $options));
        $text = trim($this->extractText($response));

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            // Tolerate the model wrapping JSON in prose / code fences.
            if (preg_match('/\{.*\}/s', $text, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Claude returned non-JSON structured output: '.mb_substr($text, 0, 200));
        }

        return $decoded;
    }

    /**
     * Build an Anthropic image content block from a public URL.
     */
    public static function imageBlock(string $url): array
    {
        return ['type' => 'image', 'source' => ['type' => 'url', 'url' => $url]];
    }

    public static function textBlock(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    private function payload(array $messages, ?string $system, array $options): array
    {
        $payload = array_merge([
            'model' => $this->config['model'],
            'max_tokens' => $this->config['max_tokens'],
            'messages' => $messages,
        ], $options);

        if ($system) {
            $payload['system'] = $system;
        }

        return $payload;
    }

    private function raw(array $payload): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Anthropic API key is not configured (boshpana.ai.api_key).');
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => $this->config['version'],
            'content-type' => 'application/json',
        ])
            ->timeout($this->config['timeout'])
            ->retry(2, 500, throw: false)
            ->post(rtrim($this->config['base_url'], '/').'/v1/messages', $payload);

        if ($response->failed()) {
            Log::warning('Anthropic request failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException('Anthropic request failed: HTTP '.$response->status());
        }

        return $response->json() ?? [];
    }

    private function extractText(array $response): string
    {
        $out = '';
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $out .= $block['text'];
            }
        }

        return $out;
    }
}
