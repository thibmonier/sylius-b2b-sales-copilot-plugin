<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RecognizedRequestTest extends WebTestCase
{
    use LogsInShopUserTrait;

    public function testRecognizedRequestReturnsRecordedResponseInLessThanOneSecond(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        // Test scénario: demande "état de mon panier" enregistrée dans cassettes.jsonl
        $startTime = microtime(true);

        $client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'état de mon panier',
        ], \JSON_THROW_ON_ERROR));

        $elapsedTime = microtime(true) - $startTime;

        self::assertResponseIsSuccessful();
        self::assertLessThan(1.0, $elapsedTime, 'Response should be returned in less than 1 second');

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($response);
        self::assertArrayHasKey('message', $response);
        self::assertIsString($response['message']);
        self::assertStringContainsString('panier', $response['message']);
    }
}
