<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\RateLimit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\Policy\FixedWindowLimiter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Tmonier\SyliusB2bSalesCopilotPlugin\RateLimit\CopilotRateLimiter;

final class CopilotRateLimiterTest extends TestCase
{
    public function testAllowsRequestWhenLimitNotExceeded(): void
    {
        $factory = new RateLimiterFactory(
            [
                'id' => 'copilot_test',
                'policy' => 'fixed_window',
                'limit' => 5,
                'interval' => '1 day',
            ],
            new InMemoryStorage(),
        );

        $rateLimiter = new CopilotRateLimiter($factory);

        self::assertTrue($rateLimiter->isAllowed('member-123'));
        self::assertTrue($rateLimiter->isAllowed('member-123'));
        self::assertTrue($rateLimiter->isAllowed('member-123'));
    }

    public function testDeniesRequestWhenLimitExceeded(): void
    {
        $factory = new RateLimiterFactory(
            [
                'id' => 'copilot_test',
                'policy' => 'fixed_window',
                'limit' => 2,
                'interval' => '1 day',
            ],
            new InMemoryStorage(),
        );

        $rateLimiter = new CopilotRateLimiter($factory);

        self::assertTrue($rateLimiter->isAllowed('member-456'));
        self::assertTrue($rateLimiter->isAllowed('member-456'));
        self::assertFalse($rateLimiter->isAllowed('member-456'));
    }

    public function testDifferentMembersHaveSeparateLimits(): void
    {
        $factory = new RateLimiterFactory(
            [
                'id' => 'copilot_test',
                'policy' => 'fixed_window',
                'limit' => 1,
                'interval' => '1 day',
            ],
            new InMemoryStorage(),
        );

        $rateLimiter = new CopilotRateLimiter($factory);

        self::assertTrue($rateLimiter->isAllowed('member-abc'));
        self::assertFalse($rateLimiter->isAllowed('member-abc'));

        self::assertTrue($rateLimiter->isAllowed('member-xyz'));
        self::assertFalse($rateLimiter->isAllowed('member-xyz'));
    }
}
