<?php

return [
    'enabled' => (bool) env('AI_EVALUATION_ENABLED', false),
    'selection_enabled' => (bool) env('AI_SELECTION_ENABLED', false),
    'service_url' => env('AI_SERVICE_URL', 'http://127.0.0.1:8000'),
    'token' => env('AI_SERVICE_TOKEN'),
    'technical_evaluator_id' => env('AI_TECHNICAL_EVALUATOR_ID'),
    'selection_evaluator_id' => env('AI_SELECTION_EVALUATOR_ID'),
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 5),
    'timeout' => (int) env('AI_EVALUATION_TIMEOUT', 60),
    'tries' => (int) env('AI_EVALUATION_TRIES', 3),
    'queue' => env('AI_EVALUATION_QUEUE', 'ai-evaluations'),
    'knowledge_version' => env('AI_KNOWLEDGE_VERSION'),
    'prompts' => [
        'technical' => 'technical_evaluator_v1',
        'selection' => 'selection_reviewer_v1',
    ],
];
