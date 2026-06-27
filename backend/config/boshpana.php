<?php

return [
    'brand' => 'Boshpana.ai',

    // AI provider. 'deepseek' (default, for now) or 'claude'.
    'ai' => [
        'provider' => env('AI_PROVIDER', 'deepseek'),

        // Anthropic / Claude (read by AnthropicClient).
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-4-8'),
        'version' => '2023-06-01',
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 4096),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 120),

        // DeepSeek (OpenAI-compatible; read by DeepSeekClient). Text-only.
        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'max_tokens' => (int) env('DEEPSEEK_MAX_TOKENS', 4096),
            'temperature' => (float) env('DEEPSEEK_TEMPERATURE', 0.4),
            'timeout' => (int) env('DEEPSEEK_TIMEOUT', 120),
        ],
    ],

    // Shared bearer tokens for the auxiliary services that call our API.
    'tokens' => [
        'bot' => env('BOT_API_TOKEN', 'local-bot-token'),
        'ingest' => env('INGEST_API_TOKEN', 'local-ingest-token'),
        'userbot' => env('USERBOT_API_TOKEN', 'local-userbot-token'),
    ],

    // Search engine tuning
    'search' => [
        // Minimum match score (0..100) for a listing to become a candidate.
        'min_score' => (int) env('SEARCH_MIN_SCORE', 55),
        // How many candidate listings the AI will contact per search.
        'max_candidates' => (int) env('SEARCH_MAX_CANDIDATES', 60),
        // Free searches granted to every new user.
        'free_searches' => (int) env('SEARCH_FREE_LIMIT', 3),
        // Lookback window for "fresh" listings, in hours.
        'fresh_hours' => (int) env('SEARCH_FRESH_HOURS', 24),
        // REAL mode: how many top candidates the userbot actually contacts
        // (real conversations need a human to reply, so keep this small).
        'real_max_contacts' => (int) env('SEARCH_REAL_MAX_CONTACTS', 3),
    ],

    // When true, searches run fully offline: listings come from seed data and
    // owner conversations are role-played by the AI (no real Telegram traffic).
    'simulation_default' => (bool) env('SIMULATION_DEFAULT', true),
];
