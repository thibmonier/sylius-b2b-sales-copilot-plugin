<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReorderScenarioTest extends WebTestCase
{
    use LogsInShopUserTrait;

    public function testReorderLastMonthScenarioReturnsRecordedResponseInFrench(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        // Test scénario: "recommande comme le mois dernier" (FR)
        $client->request('POST', '/fr_FR/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'recommande comme le mois dernier',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($response);
        self::assertArrayHasKey('message', $response);
        self::assertStringContainsString('commande n° 42', $response['message']);
        self::assertArrayHasKey('tools', $response);
        self::assertIsArray($response['tools']);
        self::assertCount(2, $response['tools']);
        self::assertSame('list_my_orders', $response['tools'][0]['name']);
        self::assertSame('reorder', $response['tools'][1]['name']);
    }

    public function testReorderLastMonthScenarioReturnsRecordedResponseInEnglish(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        // Test scénario: "reorder like last month" (EN)
        $client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'reorder like last month',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($response);
        self::assertArrayHasKey('message', $response);
        self::assertStringContainsString('order #42', $response['message']);
        self::assertArrayHasKey('tools', $response);
        self::assertIsArray($response['tools']);
        self::assertCount(2, $response['tools']);
    }

    public function testReorderWithSubstitutionScenarioReturnsPartialResultInFrench(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        // Test scénario: "recommande avec substitution" (FR)
        $client->request('POST', '/fr_FR/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'recommande avec substitution',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($response);
        self::assertArrayHasKey('message', $response);
        self::assertStringContainsString('substitution', $response['message']);
        self::assertArrayHasKey('tools', $response);
        self::assertIsArray($response['tools']);
        self::assertCount(2, $response['tools']);
        self::assertSame('partial', $response['tools'][1]['result']);
    }

    public function testReorderWithSubstitutionScenarioReturnsPartialResultInEnglish(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        // Test scénario: "reorder with substitution" (EN)
        $client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'reorder with substitution',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertNotFalse($content);

        $response = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($response);
        self::assertArrayHasKey('message', $response);
        self::assertStringContainsString('substitution', $response['message']);
        self::assertArrayHasKey('tools', $response);
        self::assertIsArray($response['tools']);
        self::assertCount(2, $response['tools']);
    }
}
