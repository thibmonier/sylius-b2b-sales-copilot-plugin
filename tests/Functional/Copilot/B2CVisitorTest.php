<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class B2CVisitorTest extends WebTestCase
{
    /**
     * NOTE: This test is a placeholder. Sylius test fixtures do not distinguish
     * B2B vs B2C customers, so we cannot test this scenario properly without
     * custom fixtures or a trait that identifies B2B customers.
     *
     * Current test: verify that unauthenticated users cannot access the API
     */
    public function testUnauthenticatedUserCannotAccessCopilotApi(): void
    {
        $client = static::createClient();

        // Try to access the API without authentication
        $client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'état de mon panier',
        ], \JSON_THROW_ON_ERROR));

        // Currently returns 200 (guard missing), should return 302/401/403
        $this->assertNotEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Unauthenticated request should NOT return 200 (currently does, guard missing)',
        );
    }

    public function testWidgetIsNotPresentOnPublicPages(): void
    {
        $client = static::createClient();

        // Check that widget is not present on public pages
        $client->request('GET', '/en_US/');

        $this->assertResponseIsSuccessful();

        $crawler = $client->getCrawler();
        $widget = $crawler->filter('[data-controller*="copilot"]');
        $this->assertCount(0, $widget, 'Widget should not be present on public pages');
    }
}
