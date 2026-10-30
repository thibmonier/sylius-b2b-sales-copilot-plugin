<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;

final class CopilotChatController
{
    public function __construct(
        private readonly CopilotModelInterface $copilotModel,
    ) {
    }

    public function chat(Request $request): Response
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);

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
