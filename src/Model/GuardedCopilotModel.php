<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Model;

use Tmonier\SyliusB2bSalesCopilotPlugin\Audit\CopilotAuditLogger;
use Tmonier\SyliusB2bSalesCopilotPlugin\Guard\CopilotScopeGuard;
use Tmonier\SyliusB2bSalesCopilotPlugin\Guard\ToolWhitelist;
use Tmonier\SyliusB2bSalesCopilotPlugin\RateLimit\CopilotRateLimiter;

final readonly class GuardedCopilotModel implements CopilotModelInterface
{
    public function __construct(
        private CopilotModelInterface $decorated,
        private ToolWhitelist $whitelist,
        private CopilotScopeGuard $scopeGuard,
        private CopilotAuditLogger $auditLogger,
        private CopilotRateLimiter $rateLimiter,
    ) {
    }

    public function chat(string $userMessage, array $context): CopilotResponse
    {
        $raw = $context['member_id'] ?? 'unknown';
        $memberId = \is_string($raw) || \is_int($raw) ? (string) $raw : 'unknown';

        if (!$this->rateLimiter->isAllowed($memberId)) {
            return new CopilotResponse('Rate limit exceeded. Please try again later.', []);
        }

        $response = $this->decorated->chat($userMessage, $context);

        $allowedTools = array_filter(
            $response->tools,
            function (ToolCall $tool) use ($context): bool {
                $whitelistCheck = $this->whitelist->isAllowed($tool->name);
                $scopeCheck = $this->scopeGuard->canExecuteTool($tool, $context);

                return $whitelistCheck && $scopeCheck;
            },
        );

        $allowedTools = array_values($allowedTools);

        foreach ($allowedTools as $tool) {
            $this->auditLogger->logToolExecution($memberId, $userMessage, $tool, true);
        }

        return new CopilotResponse($response->message, $allowedTools);
    }
}
