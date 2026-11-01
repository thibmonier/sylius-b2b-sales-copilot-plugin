<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Infrastructure\Fallback;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Fallback\FallbackCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotResponse;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\ToolCall;

final class FallbackCopilotAdapterTest extends TestCase
{
    public function testDelegatesToRealAdapterOnSuccess(): void
    {
        $expected = new CopilotResponse('real response', [new ToolCall('add_to_cart', ['ref' => 'A'], 'ok')]);

        $real = $this->createMock(CopilotModelInterface::class);
        $real->method('chat')->willReturn($expected);

        $replay = $this->createMock(CopilotModelInterface::class);
        $replay->expects($this->never())->method('chat');

        $adapter = new FallbackCopilotAdapter($real, $replay, new NullLogger());
        $result = $adapter->chat('ajoute A', []);

        self::assertSame('real response', $result->message);
        self::assertCount(1, $result->tools);
    }

    public function testFallsBackToReplayOnException(): void
    {
        $real = $this->createMock(CopilotModelInterface::class);
        $real->method('chat')->willThrowException(new \RuntimeException('LLM timeout'));

        $replayResponse = new CopilotResponse('replay response', []);
        $replay = $this->createMock(CopilotModelInterface::class);
        $replay->method('chat')->willReturn($replayResponse);

        $adapter = new FallbackCopilotAdapter($real, $replay, new NullLogger());
        $result = $adapter->chat('ajoute B', []);

        self::assertStringContainsString('replay response', $result->message);
        self::assertStringContainsString('(réponse enregistrée)', $result->message);
    }
}
