<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Model;

final class ToolCall
{
    /**
     * @param array<string, mixed> $args
     * @param string|array<string, mixed> $result
     */
    public function __construct(
        public readonly string $name,
        public readonly array $args,
        public readonly string|array $result,
    ) {
    }
}
