<?php

namespace App\Services\Ai;

/**
 * Provider-agnostic chat client used by the AI services (ListingAnalyzer,
 * OwnerNegotiator). Implemented by AnthropicClient (Claude) and DeepSeekClient.
 *
 * Messages use the Anthropic-style shape: [['role' => 'user'|'assistant',
 * 'content' => string | array-of-blocks]]. Text-only providers flatten the
 * content blocks (and skip images).
 */
interface AiClient
{
    /** True when the provider is configured (API key present). */
    public function enabled(): bool;

    /** The model id in use. */
    public function model(): string;

    /** Plain-text completion. */
    public function text(array $messages, ?string $system = null, array $options = []): string;

    /**
     * JSON completion constrained to (or guided by) a JSON schema. Returns the
     * decoded associative array.
     */
    public function structured(array $messages, array $schema, ?string $system = null, array $options = []): array;
}
