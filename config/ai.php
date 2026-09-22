<?php

return [
    'enabled' => env('AI_ENABLED', true),
    'provider' => env('AI_PROVIDER', 'gemini'),
    'model' => env('AI_MODEL', 'gemini-3.8-flash'),
    'api_key' => env('GEMINI_API_KEY', ''),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 1500),
    'temperature' => (float) env('AI_TEMPERATURE', 0.7),
    'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT', 30),
    'max_message_chars' => 1000,
    'max_history_messages' => 12,

    'assistant' => [
        'name' => 'Trợ lý Mộc An',
        'welcome_message' => 'Xin chào! Tôi là Trợ lý Mộc An. Tôi có thể hỗ trợ bạn tìm kiếm nội thất gỗ tự nhiên, kiểm tra tồn kho, gợi ý bài trí hoặc tra cứu tình trạng đơn hàng. Bạn đang quan tâm đến món đồ nào?',
    ],

    'sources' => [
        'faq' => true,
        'cms' => true,
        'policies' => true,
        'blog' => true,
    ],
];
