<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Model;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Tmonier\SyliusB2bSalesCopilotPlugin\Audit\CopilotAuditLogger;
use Tmonier\SyliusB2bSalesCopilotPlugin\Guard\CopilotScopeGuard;
use Tmonier\SyliusB2bSalesCopilotPlugin\Guard\ToolWhitelist;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotResponse;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\GuardedCopilotModel;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\ToolCall;
use Tmonier\SyliusB2bSalesCopilotPlugin\RateLimit\CopilotRateLimiter;

final class GuardedCopilotModelTest extends TestCase
{
    public function testFiltersToolsBasedOnWhitelist(): void
    {
        $innerModel = $this->createMock(CopilotModelInterface::class);
        $innerModel->method('chat')->willReturn(new CopilotResponse(
            'Response with tools',
            [
                new ToolCall('view_cart', [], 'success'),
                new ToolCall('forbidden_tool', [], 'success'),
                new ToolCall('search_products', [], 'success'),
            ],
        ));

        $whitelist = new ToolWhitelist(['view_cart', 'search_products']);
        $scopeGuard = new CopilotScopeGuard();
        $auditLogger = new CopilotAuditLogger(new NullLogger());
        $rateLimiter = $this->createRateLimiter();

        $guarded = new GuardedCopilotModel($innerModel, $whitelist, $scopeGuard, $auditLogger, $rateLimiter);

        $response = $guarded->chat('test message', [
            'member_id' => 'member-123',
            'member_scopes' => ['b2b:read'],
        ]);

        self::assertCount(2, $response->tools);
        self::assertSame('view_cart', $response->tools[0]->name);
        self::assertSame('search_products', $response->tools[1]->name);
    }

    public function testFiltersToolsBasedOnScopes(): void
    {
        $innerModel = $this->createMock(CopilotModelInterface::class);
        $innerModel->method('chat')->willReturn(new CopilotResponse(
            'Response with tools',
            [
                new ToolCall('view_cart', [], 'success'),
                new ToolCall('add_to_cart', [], 'success'),
            ],
        ));

        $whitelist = new ToolWhitelist(['view_cart', 'add_to_cart']);
        $scopeGuard = new CopilotScopeGuard();
        $auditLogger = new CopilotAuditLogger(new NullLogger());
        $rateLimiter = $this->createRateLimiter();

        $guarded = new GuardedCopilotModel($innerModel, $whitelist, $scopeGuard, $auditLogger, $rateLimiter);

        $response = $guarded->chat('test message', [
            'member_id' => 'member-123',
            'member_scopes' => ['b2b:read'],
        ]);

        self::assertCount(1, $response->tools);
        self::assertSame('view_cart', $response->tools[0]->name);
    }

    public function testAllowsAllToolsWhenBothChecksPass(): void
    {
        $innerModel = $this->createMock(CopilotModelInterface::class);
        $innerModel->method('chat')->willReturn(new CopilotResponse(
            'Response with tools',
            [
                new ToolCall('view_cart', [], 'success'),
                new ToolCall('add_to_cart', [], 'success'),
            ],
        ));

        $whitelist = new ToolWhitelist(['view_cart', 'add_to_cart']);
        $scopeGuard = new CopilotScopeGuard();
        $auditLogger = new CopilotAuditLogger(new NullLogger());
        $rateLimiter = $this->createRateLimiter();

        $guarded = new GuardedCopilotModel($innerModel, $whitelist, $scopeGuard, $auditLogger, $rateLimiter);

        $response = $guarded->chat('test message', [
            'member_id' => 'member-123',
            'member_scopes' => ['b2b:read', 'b2b:cart'],
        ]);

        self::assertCount(2, $response->tools);
    }

    public function testKeepsOriginalMessageEvenWhenToolsFiltered(): void
    {
        $innerModel = $this->createMock(CopilotModelInterface::class);
        $innerModel->method('chat')->willReturn(new CopilotResponse(
            'Original message from model',
            [
                new ToolCall('forbidden_tool', [], 'success'),
            ],
        ));

        $whitelist = new ToolWhitelist(['view_cart']);
        $scopeGuard = new CopilotScopeGuard();
        $auditLogger = new CopilotAuditLogger(new NullLogger());
        $rateLimiter = $this->createRateLimiter();

        $guarded = new GuardedCopilotModel($innerModel, $whitelist, $scopeGuard, $auditLogger, $rateLimiter);

        $response = $guarded->chat('test message', [
            'member_id' => 'member-123',
            'member_scopes' => ['b2b:read'],
        ]);

        self::assertSame('Original message from model', $response->message);
        self::assertEmpty($response->tools);
    }

    public function testDeniesRequestWhenRateLimitExceeded(): void
    {
        $innerModel = $this->createMock(CopilotModelInterface::class);
        $innerModel->expects(self::once())
            ->method('chat')
            ->willReturn(new CopilotResponse('First response', []));

        $whitelist = new ToolWhitelist(['view_cart']);
        $scopeGuard = new CopilotScopeGuard();
        $auditLogger = new CopilotAuditLogger(new NullLogger());
        $rateLimiter = $this->createRateLimiter(1);

        $guarded = new GuardedCopilotModel($innerModel, $whitelist, $scopeGuard, $auditLogger, $rateLimiter);

        $context = ['member_id' => 'member-123', 'member_scopes' => ['b2b:read']];

        $firstResponse = $guarded->chat('first message', $context);
        self::assertSame('First response', $firstResponse->message);

        $secondResponse = $guarded->chat('second message', $context);
        self::assertStringContainsString('limit', strtolower($secondResponse->message));
        self::assertEmpty($secondResponse->tools);
    }

    private function createRateLimiter(int $limit = 100): CopilotRateLimiter
    {
        $factory = new RateLimiterFactory(
            [
                'id' => 'copilot_test',
                'policy' => 'fixed_window',
                'limit' => $limit,
                'interval' => '1 day',
            ],
            new InMemoryStorage(),
        );

        return new CopilotRateLimiter($factory);
    }
}
