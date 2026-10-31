<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * T-TECH-39-01: the widget renders on the B2B dashboard with locale-prefixed URLs and no raw translation keys.
 */
final class WidgetOnDashboardTest extends WebTestCase
{
    use LogsInShopUserTrait;

    public function testWidgetAppearsOnDashboardForAuthenticatedUser(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        $crawler = $client->request('GET', '/en_US/account/dashboard');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('[data-controller="tmonier-copilot"]')->count(), 'The copilot widget should be present on the dashboard.');
    }

    public function testChatEndpointUrlContainsLocalePrefix(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        $crawler = $client->request('GET', '/en_US/account/dashboard');

        self::assertResponseIsSuccessful();

        $widget = $crawler->filter('[data-controller="tmonier-copilot"]');
        self::assertGreaterThan(0, $widget->count());

        $apiUrl = $widget->attr('data-tmonier-copilot-api-url-value');
        self::assertNotNull($apiUrl);
        self::assertStringContainsString('/en_US/', $apiUrl, 'The chat endpoint URL must contain the locale prefix.');
    }

    public function testWidgetContainsNoRawTranslationKeys(): void
    {
        $client = static::createClient();
        $this->logInNewShopUser($client);

        $crawler = $client->request('GET', '/en_US/account/dashboard');

        self::assertResponseIsSuccessful();

        $widget = $crawler->filter('[data-controller="tmonier-copilot"]');
        self::assertGreaterThan(0, $widget->count());

        $visibleText = strip_tags($widget->html());

        self::assertDoesNotMatchRegularExpression(
            '/\b[a-z_]{2,}\.[a-z_.]{2,}\b/',
            $visibleText,
            'Raw translation key found in the rendered copilot widget.',
        );
    }
}
