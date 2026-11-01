<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\SymfonyAi;

use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\PlatformInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotResponse;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\ToolCall;

final readonly class SymfonyAiCopilotAdapter implements CopilotModelInterface
{
    /** @param non-empty-string $model */
    public function __construct(
        private PlatformInterface $platform,
        private string $model,
        private string $systemPrompt,
    ) {
    }

    public function chat(string $userMessage, array $context): CopilotResponse
    {
        $messages = new MessageBag();
        $messages->add(new SystemMessage($this->systemPrompt));
        $messages->add(new UserMessage(new Text($userMessage)));

        $deferred = $this->platform->invoke($this->model, $messages);

        $text = '';
        $tools = [];

        try {
            $text = $deferred->asText();
        } catch (\Throwable) {
            // Result may not be a text result — try tool calls
        }

        try {
            $aiToolCalls = $deferred->asToolCalls();
            foreach ($aiToolCalls as $aiTool) {
                $tools[] = new ToolCall(
                    $aiTool->getName(),
                    $aiTool->getArguments(),
                    'pending',
                );
            }
        } catch (\Throwable) {
            // No tool calls in this response
        }

        if ('' === $text && [] === $tools) {
            $text = '(empty response from the model)';
        }

        return new CopilotResponse($text, $tools);
    }
}
