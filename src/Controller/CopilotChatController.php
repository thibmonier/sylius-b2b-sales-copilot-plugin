<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Controller;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;

final class CopilotChatController
{
    /**
     * @param array<string> $enabledChannels
     */
    public function __construct(
        private readonly CopilotModelInterface $copilotModel,
        private readonly CustomerContextInterface $customerContext,
        private readonly ChannelContextInterface $channelContext,
        private readonly array $enabledChannels,
    ) {
    }

    public function chat(Request $request): Response
    {
        // Guard: Check authentication
        try {
            $customer = $this->customerContext->getCustomer();
        } catch (\Exception) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if ($customer === null) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // Guard: Check enabled channel
        $currentChannel = $this->channelContext->getChannel();
        if (!in_array($currentChannel->getCode(), $this->enabledChannels, true)) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Guard: Validate JSON
        try {
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($data) || !isset($data['message']) || !is_string($data['message'])) {
            return new JsonResponse(['error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        $response = $this->copilotModel->chat($data['message'], []);

        return new JsonResponse([
            'message' => $response->message,
            'tools' => array_map(
                static fn ($tool) => [
                    'name' => $tool->name,
                    'args' => $tool->args,
                    'result' => $tool->result,
                ],
                $response->tools,
            ),
        ]);
    }
}
