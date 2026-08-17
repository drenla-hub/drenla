<?php

namespace App\AiAssistant\Services;

use App\AiAssistant\Contracts\AssistantTool;
use InvalidArgumentException;

class AssistantToolRegistry
{
    /**
     * @param  iterable<AssistantTool>  $tools
     */
    public function __construct(
        iterable $tools = [],
    ) {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    /**
     * @var array<string, AssistantTool>
     */
    protected array $tools = [];

    public function register(AssistantTool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function get(string $name): AssistantTool
    {
        if (! isset($this->tools[$name])) {
            throw new InvalidArgumentException("Unknown assistant tool [{$name}].");
        }

        return $this->tools[$name];
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, array<string, mixed>>
     */
    public function definitions(array $names): array
    {
        return array_values(array_map(
            fn (string $name): array => $this->get($name)->definition(),
            $names
        ));
    }
}
