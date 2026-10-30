<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Replay;

use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotResponse;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\ToolCall;

/**
 * Replay adapter: reads pre-recorded responses from a JSONL cassette file.
 * Never makes LLM API calls. Returns a fallback message for unrecognized requests.
 */
final class ReplayCopilotAdapter implements CopilotModelInterface
{
    private const FALLBACK_MESSAGE = 'Scénario non enregistré en démo. Veuillez réessayer avec une demande connue.';

    /** @var array<string, array{intent: string, response: string, tools: array<int, array{name: string, args: array<string, mixed>, result: string}>}> */
    private array $cassettes = [];

    public function __construct(
        private readonly string $cassettePath,
    ) {
        $this->loadCassettes();
    }

    public function chat(string $userMessage, array $context): CopilotResponse
    {
        $key = $this->normalizeKey($userMessage);
        $cassette = $this->cassettes[$key] ?? null;

        if (null === $cassette) {
            return new CopilotResponse(self::FALLBACK_MESSAGE);
        }

        $tools = array_map(
            fn (array $tool): ToolCall => new ToolCall($tool['name'], $tool['args'], $tool['result']),
            $cassette['tools'],
        );

        return new CopilotResponse($cassette['response'], $tools);
    }

    private function loadCassettes(): void
    {
        if (!file_exists($this->cassettePath)) {
            return;
        }

        $lines = file($this->cassettePath, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
        if (false === $lines) {
            return;
        }

        foreach ($lines as $line) {
            $data = json_decode($line, true, 512, \JSON_THROW_ON_ERROR);
            if (!is_array($data) || !isset($data['intent'], $data['response'], $data['tools'])) {
                continue;
            }

            $key = $this->normalizeKey($data['intent']);
            $this->cassettes[$key] = $data;
        }
    }

    private function normalizeKey(string $message): string
    {
        return hash('sha256', mb_strtolower(trim($message)));
    }
}
