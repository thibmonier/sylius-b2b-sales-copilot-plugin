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
    /** @var array<string, array{intent: string, aliases: list<string>, response: string, tools: list<ToolCall>}> */
    private array $cassettes = [];

    /** @var list<array{intent: string, aliases: list<string>, response: string, tools: list<ToolCall>}> */
    private array $cassettesList = [];

    public function __construct(
        private readonly string $cassettePath,
    ) {
        $this->loadCassettes();
    }

    public function chat(string $userMessage, array $context): CopilotResponse
    {
        // 1. Try exact match on intent
        $key = $this->normalizeKey($userMessage);
        $cassette = $this->cassettes[$key] ?? null;

        if (null !== $cassette) {
            return new CopilotResponse($cassette['response'], $cassette['tools']);
        }

        // 2. Try alias match
        $cassette = $this->findByAlias($userMessage);
        if (null !== $cassette) {
            return new CopilotResponse($cassette['response'], $cassette['tools']);
        }

        // 3. Try keyword match
        $cassette = $this->findByKeywords($userMessage);
        if (null !== $cassette) {
            return new CopilotResponse($cassette['response'], $cassette['tools']);
        }

        // 4. Return improved fallback with suggestions
        return new CopilotResponse($this->buildFallbackMessage($userMessage));
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

            if (!is_string($data['intent']) || !is_string($data['response']) || !is_array($data['tools'])) {
                continue;
            }

            $aliases = [];
            if (isset($data['aliases']) && is_array($data['aliases'])) {
                foreach ($data['aliases'] as $alias) {
                    if (is_string($alias)) {
                        $aliases[] = $alias;
                    }
                }
            }

            $tools = [];
            foreach ($data['tools'] as $toolData) {
                if (!is_array($toolData) || !isset($toolData['name'], $toolData['args'], $toolData['result'])) {
                    continue;
                }
                if (!is_string($toolData['name']) || !is_array($toolData['args'])) {
                    continue;
                }
                if (!is_string($toolData['result']) && !is_array($toolData['result'])) {
                    continue;
                }
                /** @var array<string, mixed> $args */
                $args = $toolData['args'];
                /** @var string|array<string, mixed> $result */
                $result = $toolData['result'];
                $tools[] = new ToolCall($toolData['name'], $args, $result);
            }

            $cassette = [
                'intent' => $data['intent'],
                'aliases' => $aliases,
                'response' => $data['response'],
                'tools' => $tools,
            ];

            $key = $this->normalizeKey($data['intent']);
            $this->cassettes[$key] = $cassette;
            $this->cassettesList[] = $cassette;
        }
    }

    private function normalizeKey(string $message): string
    {
        return hash('sha256', mb_strtolower(trim($message)));
    }

    /**
     * @return array{intent: string, aliases: list<string>, response: string, tools: list<ToolCall>}|null
     */
    private function findByAlias(string $userMessage): ?array
    {
        $key = $this->normalizeKey($userMessage);

        foreach ($this->cassettesList as $cassette) {
            foreach ($cassette['aliases'] as $alias) {
                if ($this->normalizeKey($alias) === $key) {
                    return $cassette;
                }
            }
        }

        return null;
    }

    /**
     * @return array{intent: string, aliases: list<string>, response: string, tools: list<ToolCall>}|null
     */
    private function findByKeywords(string $userMessage): ?array
    {
        $userWords = $this->extractKeywords($userMessage);
        if (empty($userWords)) {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($this->cassettesList as $cassette) {
            $allPhrases = array_merge([$cassette['intent']], $cassette['aliases']);

            foreach ($allPhrases as $phrase) {
                $phraseWords = $this->extractKeywords($phrase);
                $score = count(array_intersect($userWords, $phraseWords));

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $cassette;
                }
            }
        }

        // Require at least 2 matching keywords to avoid false positives
        return $bestScore >= 2 ? $bestMatch : null;
    }

    /**
     * @return list<string>
     */
    private function extractKeywords(string $text): array
    {
        $normalized = mb_strtolower(trim($text));

        // Remove common stop words (French and English)
        $stopWords = [
            'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'mon', 'ma', 'mes',
            'the', 'a', 'an', 'of', 'my', 'in', 'with', 'like', 'as',
            'et', 'ou', 'que', 'qui', 'est', 'sont', 'peux', 'tu', 'vous',
            'and', 'or', 'is', 'are', 'can', 'you', 'what', 'how',
        ];

        $words = preg_split('/\s+/', $normalized, -1, \PREG_SPLIT_NO_EMPTY);
        if (false === $words) {
            return [];
        }

        return array_values(array_filter(
            $words,
            fn (string $word) => !in_array($word, $stopWords, true) && mb_strlen($word) > 2,
        ));
    }

    private function buildFallbackMessage(string $userMessage): string
    {
        $isFrench = $this->detectLanguage($userMessage);

        if (empty($this->cassettesList)) {
            return $isFrench
                ? 'Scénario non enregistré en démo. Veuillez réessayer avec une demande connue.'
                : 'Scenario not recorded in demo. Please try again with a known request.';
        }

        $scenarios = array_map(
            fn (array $cassette) => $cassette['intent'],
            $this->cassettesList,
        );

        if ($isFrench) {
            $frenchScenarios = array_filter(
                $scenarios,
                fn (string $intent) => $this->detectLanguage($intent),
            );

            if (!empty($frenchScenarios)) {
                $list = implode("\n- ", $frenchScenarios);

                return "Scénario non enregistré en démo.\n\nScénarios disponibles :\n- {$list}";
            }
        } else {
            $englishScenarios = array_filter(
                $scenarios,
                fn (string $intent) => !$this->detectLanguage($intent),
            );

            if (!empty($englishScenarios)) {
                $list = implode("\n- ", $englishScenarios);

                return "Scenario not recorded in demo.\n\nAvailable scenarios:\n- {$list}";
            }
        }

        // Fallback if no scenarios in detected language
        return $isFrench
            ? 'Scénario non enregistré en démo. Veuillez réessayer avec une demande connue.'
            : 'Scenario not recorded in demo. Please try again with a known request.';
    }

    private function detectLanguage(string $text): bool
    {
        // Simple heuristic: detect French by common French words (whole word match)
        $frenchIndicators = ['mon', 'ma', 'mes', 'le', 'la', 'les', 'de', 'du', 'panier', 'commande', 'avec', 'dernier', 'comme', 'demande', 'quel', 'quels', 'ajoute', 'état', 'référence', 'inconnue'];
        $normalized = mb_strtolower($text);

        // Split into words
        $words = preg_split('/\s+/', $normalized, -1, \PREG_SPLIT_NO_EMPTY);
        if (false === $words) {
            return false;
        }

        foreach ($words as $word) {
            if (in_array($word, $frenchIndicators, true)) {
                return true;
            }
        }

        return false;
    }
}
