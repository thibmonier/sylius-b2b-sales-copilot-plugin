<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Infrastructure\SymfonyAi;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\Result\ToolCall as AiToolCall;
use Symfony\AI\Platform\Result\ToolCallResult;
use Symfony\AI\Platform\Test\InMemoryPlatform;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\SymfonyAi\SymfonyAiCopilotAdapter;

/** Uses the in-memory platform shipped with Symfony AI: no network, no real LLM. */
final class SymfonyAiCopilotAdapterResultTypesTest extends TestCase
{
    public function testToolCallsOfThePlatformAreMappedToPendingCopilotToolCalls(): void
    {
        $platform = new InMemoryPlatform(static fn () => new ToolCallResult([
            new AiToolCall('call-1', 'search_products', ['query' => 'gants']),
            new AiToolCall('call-2', 'add_to_cart', ['sku' => 'G-001', 'quantity' => 3]),
        ]));
        $adapter = new SymfonyAiCopilotAdapter($platform, 'claude-sonnet-4-5', 'prompt');

        $response = $adapter->chat('ajoute 3 gants', []);

        self::assertSame('', $response->message);
        self::assertCount(2, $response->tools);
        self::assertSame('search_products', $response->tools[0]->name);
        self::assertSame(['query' => 'gants'], $response->tools[0]->args);
        self::assertSame('pending', $response->tools[0]->result);
        self::assertSame('add_to_cart', $response->tools[1]->name);
        self::assertSame(['sku' => 'G-001', 'quantity' => 3], $response->tools[1]->args);
    }

    public function testEmptyTextAnswerIsReplacedByAPlaceholderMessage(): void
    {
        $adapter = new SymfonyAiCopilotAdapter(new InMemoryPlatform(''), 'claude-sonnet-4-5', 'prompt');

        $response = $adapter->chat('bonjour', []);

        self::assertSame('(empty response from the model)', $response->message);
        self::assertSame([], $response->tools);
    }

    public function testTextAnswerCarriesNoToolCall(): void
    {
        $adapter = new SymfonyAiCopilotAdapter(new InMemoryPlatform('Bonjour'), 'claude-sonnet-4-5', 'prompt');

        $response = $adapter->chat('bonjour', []);

        self::assertSame('Bonjour', $response->message);
        self::assertSame([], $response->tools);
    }
}
