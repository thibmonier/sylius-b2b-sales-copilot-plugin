<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Audit;

use Psr\Log\LoggerInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\ToolCall;

final readonly class CopilotAuditLogger
{
    public function __construct(private LoggerInterface $copilotAuditLogger)
    {
    }

    public function logToolExecution(string $memberId, string $intent, ToolCall $tool, bool $success): void
    {
        $context = [
            'member_id' => $memberId,
            'intent' => $intent,
            'tool_name' => $tool->name,
            'tool_args' => $tool->args,
            'success' => $success,
        ];

        if ($success) {
            $this->copilotAuditLogger->info('Copilot tool executed', $context);
        } else {
            $this->copilotAuditLogger->warning('Copilot tool execution failed', $context);
        }
    }
}
