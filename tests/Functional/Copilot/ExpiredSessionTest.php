<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ExpiredSessionTest extends WebTestCase
{
    public function testUnauthenticatedRequestRedirectsOrForbidden(): void
    {
        $client = static::createClient();

        // Try to send a message without being authenticated
        $client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'état de mon panier',
        ], \JSON_THROW_ON_ERROR));

        // Should redirect to login (302) or return 401/403, NOT 200
        $this->assertNotEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Unauthenticated request should NOT return 200 (currently does, guard missing)',
        );

        $this->assertContains(
            $client->getResponse()->getStatusCode(),
            [302, 401, 403],
            'Unauthenticated request should be redirected or forbidden',
        );
    }

    public function testUnauthenticatedRequestDoesNotLeakData(): void
    {
        $client = static::createClient();

        // Try to call API without being authenticated
        $client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'état de mon panier',
        ], \JSON_THROW_ON_ERROR));

        // Response should not contain cart data
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        // If 200, should not contain any data (currently it might)
        if ($client->getResponse()->getStatusCode() === 200) {
            $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
            $this->assertIsArray($data);
            // Should not have actual cart data
            $this->assertArrayNotHasKey('cart', $data);
        }
    }

    public function testInvalidJsonReturns400(): void
    {
        $client = static::createClient();

        // Send invalid JSON
        $client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json{]');

        // Should return 400 Bad Request
        $this->assertEquals(
            400,
            $client->getResponse()->getStatusCode(),
            'Invalid JSON should return 400',
        );
    }
}
