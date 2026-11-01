<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Fallback;

use Psr\Log\LoggerInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotResponse;

final readonly class FallbackCopilotAdapter implements CopilotModelInterface
{
    public function __construct(
        private CopilotModelInterface $realAdapter,
        private CopilotModelInterface $replayAdapter,
        private LoggerInterface $logger,
    ) {
    }

    public function chat(string $userMessage, array $context): CopilotResponse
    {
        try {
            return $this->realAdapter->chat($userMessage, $context);
        } catch (\Throwable $e) {
            $this->logger->warning('Copilot LLM call failed, falling back to replay', [
                'error' => $e->getMessage(),
            ]);

            $response = $this->replayAdapter->chat($userMessage, $context);

            return new CopilotResponse(
                $response->message . "\n\n_(réponse enregistrée)_",
                $response->tools,
            );
        }
    }
}
