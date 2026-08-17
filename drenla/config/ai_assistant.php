<?php

return [
    'default' => env('AI_ASSISTANT_PROVIDER', 'null'),

    'assistants' => [
        'admin' => [
            'prompt' => 'admin',
            'tools' => [
                'list_projects',
                'project_overview',
            ],
        ],
        'public_chat' => [
            'prompt' => 'public_chat',
            // Anonymous site visitors don't get internal data-lookup tools.
            'tools' => [],
        ],
        'journal' => [
            'prompt' => 'journal',
            // Same tool access as the admin assistant — still an internal, admin-context assistant.
            'tools' => [
                'list_projects',
                'project_overview',
            ],
        ],
    ],

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-5.4'),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.5-pro'),
        ],
    ],
];
