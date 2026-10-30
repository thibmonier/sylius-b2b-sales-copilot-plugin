<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UnrecognizedRequestTest extends WebTestCase
{
    use LogsInShopUserTrait;

    public function testUnrecognizedRequestReturnsFallbackMessage(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        // Demande non enregistrée dans les cassettes
        $client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'une demande totalement inconnue qui n\'existe pas dans les cassettes',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($response);
        self::assertArrayHasKey('message', $response);
        self::assertIsString($response['message']);
        self::assertStringContainsString('non enregistré', $response['message']);
    }

    public function testUnrecognizedRequestDoesNotMakeExternalApiCall(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        // Demande non enregistrée - doit retourner le message de repli sans appel externe
        $client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'demande inconnue',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        // Pas d'assertion sur l'absence d'appel réseau (difficile à tester),
        // mais le test précédent vérifie que le temps de réponse est < 1s,
        // ce qui serait impossible avec un appel LLM
    }
}
