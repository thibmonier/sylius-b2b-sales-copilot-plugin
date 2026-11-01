<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Infrastructure\CostLimit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\CostLimit\DailyCostLimiter;

final class DailyCostLimiterTest extends TestCase
{
    public function testAllowedWhenUnderLimit(): void
    {
        $cache = new ArrayAdapter();
        $limiter = new DailyCostLimiter($cache, 500);

        self::assertTrue($limiter->isAllowed());
    }

    public function testDeniedWhenAtLimit(): void
    {
        $cache = new ArrayAdapter();
        $limiter = new DailyCostLimiter($cache, 100);

        $limiter->recordCost(100);

        self::assertFalse($limiter->isAllowed());
    }

    public function testAccumulatesCosts(): void
    {
        $cache = new ArrayAdapter();
        $limiter = new DailyCostLimiter($cache, 100);

        $limiter->recordCost(30);
        self::assertTrue($limiter->isAllowed());

        $limiter->recordCost(40);
        self::assertTrue($limiter->isAllowed());

        $limiter->recordCost(31);
        self::assertFalse($limiter->isAllowed());
    }
}
