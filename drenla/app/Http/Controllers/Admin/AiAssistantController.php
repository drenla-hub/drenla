<?php

namespace App\Http\Controllers\Admin;

use App\AiAssistant\Services\AdminAssistant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function __invoke(Request $request, AdminAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:8000'],
            'context' => ['nullable', 'array'],
            'page' => ['nullable', 'string', 'max:255'],
            'page_context' => ['nullable', 'array'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'max:40'],
            'history.*.content' => ['required_with:history', 'string'],
        ]);

        $context = $validated['context'] ?? [];

        if (isset($validated['page'])) {
            $context['page'] = $validated['page'];
        }

        if (isset($validated['page_context']) && is_array($validated['page_context'])) {
            $context['page_context'] = $validated['page_context'];
        }

        $result = $assistant->respond(
            prompt: $validated['prompt'],
            context: $context,
            history: $validated['history'] ?? [],
        );

        return response()->json($result->toArray());
    }
}
