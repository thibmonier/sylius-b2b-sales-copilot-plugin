<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class B2CVisitorTest extends WebTestCase
{
    public function testB2CCustomerDoesNotSeeWidget(): void
    {
        $client = static::createClient();

        // Try to access the account dashboard as an unauthenticated visitor
        // (B2C customers are not authenticated in this test)
        $client->request('GET', '/en_US/account/dashboard');

        // Should redirect to login since not authenticated
        $this->assertResponseRedirects();

        $client->followRedirect();

        // Should be on login page, not showing the widget
        $crawler = $client->getCrawler();
        $widget = $crawler->filter('[data-controller="tmonier--sylius-b2b-sales-copilot-plugin--copilot"]');
        $this->assertCount(0, $widget, 'Widget should not be present for unauthenticated users');
    }

    public function testWidgetIsNotAccessibleFromPublicPages(): void
    {
        $client = static::createClient();

        // Check that widget is not present on public pages
        $client->request('GET', '/en_US/');

        $this->assertResponseIsSuccessful();

        $crawler = $client->getCrawler();
        $widget = $crawler->filter('[data-controller="tmonier--sylius-b2b-sales-copilot-plugin--copilot"]');
        $this->assertCount(0, $widget, 'Widget should not be present on public pages');
    }
}
