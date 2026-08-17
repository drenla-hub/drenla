<?php

namespace App\Providers;

use App\AiAssistant\Contracts\AssistantProvider;
use App\AiAssistant\Prompts\AdminAssistantPrompt;
use App\AiAssistant\Prompts\JournalAssistantPrompt;
use App\AiAssistant\Prompts\PublicChatAssistantPrompt;
use App\AiAssistant\Providers\GeminiAssistantProvider;
use App\AiAssistant\Providers\NullAssistantProvider;
use App\AiAssistant\Services\AdminAssistant;
use App\AiAssistant\Services\AiAssistantManager;
use App\AiAssistant\Services\AssistantPromptRegistry;
use App\AiAssistant\Services\AssistantToolRegistry;
use App\AiAssistant\Services\JournalAssistant;
use App\AiAssistant\Services\PublicChatAssistant;
use App\AiAssistant\Tools\ListProjectsTool;
use App\AiAssistant\Tools\ProjectOverviewTool;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AiAssistantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/ai_assistant.php', 'ai_assistant');

        $this->app->singleton(AssistantToolRegistry::class, function () {
            return new AssistantToolRegistry([
                new ListProjectsTool,
                new ProjectOverviewTool,
            ]);
        });

        $this->app->singleton(AssistantProvider::class, function () {
            $driver = (string) config('ai_assistant.default', 'null');

            return match ($driver) {
                'null' => new NullAssistantProvider,
                'gemini' => new GeminiAssistantProvider(
                    apiKey: (string) config('ai_assistant.providers.gemini.api_key', ''),
                    model: (string) config('ai_assistant.providers.gemini.model', 'gemini-3.5-flash'),
                ),
                'openai' => throw new RuntimeException("AI assistant driver [{$driver}] is declared but not implemented yet."),
                default => throw new RuntimeException("Unsupported AI assistant driver [{$driver}]."),
            };
        });

        $this->app->singleton(AssistantPromptRegistry::class, function () {
            return new AssistantPromptRegistry([
                'admin' => new AdminAssistantPrompt,
                'public_chat' => new PublicChatAssistantPrompt,
                'journal' => new JournalAssistantPrompt,
            ]);
        });

        $this->app->singleton(AiAssistantManager::class, function ($app) {
            return new AiAssistantManager(
                provider: $app->make(AssistantProvider::class),
                tools: $app->make(AssistantToolRegistry::class),
                prompts: $app->make(AssistantPromptRegistry::class),
                providerName: (string) config('ai_assistant.default', 'null'),
            );
        });

        $this->app->singleton(AdminAssistant::class);
        $this->app->singleton(PublicChatAssistant::class);
        $this->app->singleton(JournalAssistant::class);
    }
}
