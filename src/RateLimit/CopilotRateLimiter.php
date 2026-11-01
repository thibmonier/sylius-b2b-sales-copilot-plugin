<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\RateLimit;

use Symfony\Component\RateLimiter\RateLimiterFactory;

final class CopilotRateLimiter
{
    public function __construct(private readonly RateLimiterFactory $copilotLimiter)
    {
    }

    public function isAllowed(string $memberId): bool
    {
        $limiter = $this->copilotLimiter->create($memberId);
        $rateLimit = $limiter->consume(1);

        return $rateLimit->isAccepted();
    }
}
