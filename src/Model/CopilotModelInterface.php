<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Model;

interface CopilotModelInterface
{
    /**
     * Process a user message and return the copilot's response.
     *
     * @param string $userMessage The user's natural language request
     * @param array<string, mixed> $context The context (signed-in member, current cart, etc.)
     *
     * @return CopilotResponse The response with tools called and the message
     */
    public function chat(string $userMessage, array $context): CopilotResponse;
}
