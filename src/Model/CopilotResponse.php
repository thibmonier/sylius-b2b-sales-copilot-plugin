<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Model;

final class CopilotResponse
{
    /**
     * @param array<int, ToolCall> $tools
     */
    public function __construct(
        public readonly string $message,
        public readonly array $tools = [],
    ) {
    }
}
