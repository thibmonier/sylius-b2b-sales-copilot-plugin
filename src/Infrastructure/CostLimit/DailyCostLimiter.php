<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\CostLimit;

use Symfony\Contracts\Cache\CacheInterface;

final class DailyCostLimiter
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $dailyLimitCents,
    ) {
    }

    public function isAllowed(): bool
    {
        $key = $this->cacheKey();
        $current = $this->cache->get($key, static fn () => 0);

        return $current < $this->dailyLimitCents;
    }

    public function recordCost(int $cents): void
    {
        $key = $this->cacheKey();

        /** @var int $current */
        $current = $this->cache->get($key, static fn () => 0);

        $this->cache->delete($key);
        $this->cache->get($key, static fn () => $current + $cents);
    }

    private function cacheKey(): string
    {
        return 'copilot_daily_cost_' . date('Y-m-d');
    }
}
