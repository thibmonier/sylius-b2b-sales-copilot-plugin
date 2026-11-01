<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Guard;

final readonly class ToolWhitelist
{
    /**
     * @param list<string> $allowedTools
     */
    public function __construct(private array $allowedTools)
    {
    }

    public function isAllowed(string $toolName): bool
    {
        return in_array($toolName, $this->allowedTools, true);
    }
}
