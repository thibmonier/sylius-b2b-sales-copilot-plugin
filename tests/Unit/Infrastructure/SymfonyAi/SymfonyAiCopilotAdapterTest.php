<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Infrastructure\SymfonyAi;

use PHPUnit\Framework\TestCase;
use Symfony\AI\Platform\PlatformInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\SymfonyAi\SymfonyAiCopilotAdapter;

final class SymfonyAiCopilotAdapterTest extends TestCase
{
    public function testThrowsOnPlatformError(): void
    {
        $platform = $this->createMock(PlatformInterface::class);
        $platform->method('invoke')->willThrowException(new \RuntimeException('API error'));

        $adapter = new SymfonyAiCopilotAdapter($platform, 'claude-sonnet-4-5', 'You are a B2B copilot.');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API error');
        $adapter->chat('test', []);
    }
}
