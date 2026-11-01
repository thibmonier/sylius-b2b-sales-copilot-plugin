<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Audit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Audit\CopilotAuditLogger;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\ToolCall;

final class CopilotAuditLoggerTest extends TestCase
{
    public function testLogsSuccessfulToolExecution(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'Copilot tool executed',
                [
                    'member_id' => 'member-123',
                    'intent' => 'add product to cart',
                    'tool_name' => 'add_to_cart',
                    'tool_args' => ['reference' => 'PB-GANT-NITR-09', 'quantity' => 5],
                    'success' => true,
                ],
            );

        $auditLogger = new CopilotAuditLogger($logger);
        $tool = new ToolCall('add_to_cart', ['reference' => 'PB-GANT-NITR-09', 'quantity' => 5], 'success');

        $auditLogger->logToolExecution('member-123', 'add product to cart', $tool, true);
    }

    public function testLogsFailedToolExecution(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Copilot tool execution failed',
                [
                    'member_id' => 'member-456',
                    'intent' => 'view cart',
                    'tool_name' => 'view_cart',
                    'tool_args' => [],
                    'success' => false,
                ],
            );

        $auditLogger = new CopilotAuditLogger($logger);
        $tool = new ToolCall('view_cart', [], 'error');

        $auditLogger->logToolExecution('member-456', 'view cart', $tool, false);
    }

    public function testLogsWithComplexToolArgs(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'Copilot tool executed',
                self::callback(function (array $context) {
                    return $context['member_id'] === 'member-789'
                        && $context['tool_name'] === 'reorder'
                        && $context['tool_args'] === [
                            'order_number' => '000042',
                            'substitutions' => true,
                            'items' => ['item1', 'item2'],
                        ];
                }),
            );

        $auditLogger = new CopilotAuditLogger($logger);
        $tool = new ToolCall('reorder', [
            'order_number' => '000042',
            'substitutions' => true,
            'items' => ['item1', 'item2'],
        ], 'success');

        $auditLogger->logToolExecution('member-789', 'reorder last order', $tool, true);
    }
}
