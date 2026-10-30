<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ExpiredSessionTest extends WebTestCase
{
    public function testExpiredSessionRedirectsToLogin(): void
    {
        $client = static::createClient();

        // Try to send a message without being authenticated
        $client->request('POST', '/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'état de mon panier',
        ], \JSON_THROW_ON_ERROR));

        // Should redirect to login (302) or return 401/403
        $this->assertContains(
            $client->getResponse()->getStatusCode(),
            [302, 401, 403],
            'Unauthenticated request should be redirected or forbidden'
        );
    }

    public function testExpiredSessionDoesNotLeakData(): void
    {
        $client = static::createClient();

        // Try to access the widget without being authenticated
        $client->request('GET', '/en_US/account/dashboard');

        // Should redirect to login
        $this->assertResponseRedirects();

        // Response should not contain any sensitive data
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringNotContainsString('panier', $content);
        $this->assertStringNotContainsString('cart', $content);
    }
}
