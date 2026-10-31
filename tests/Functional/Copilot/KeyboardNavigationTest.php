<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class KeyboardNavigationTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;
    }

    public function testWidgetIsAccessibleByKeyboard(): void
    {
        // Create and login a ShopUser
        $customer = new Customer();
        $customer->setEmail('test_' . uniqid() . '@example.com');
        $customer->setFirstName('Test');
        $customer->setLastName('User');

        $shopUser = new ShopUser();
        $shopUser->setUsername('test_' . uniqid() . '@example.com');
        $shopUser->setPlainPassword('password123');
        $shopUser->setEnabled(true);
        $shopUser->setCustomer($customer);

        $this->entityManager->persist($customer);
        $this->entityManager->persist($shopUser);
        $this->entityManager->flush();

        $this->client->loginUser($shopUser, 'shop');

        // Navigate to account dashboard
        $this->client->request('GET', '/en_US/account/dashboard');

        $this->assertResponseIsSuccessful();

        // Check that the widget has accessible attributes
        $crawler = $this->client->getCrawler();

        // Widget should have role="region"
        $widget = $crawler->filter('[data-controller="tmonier-copilot"]');
        $this->assertCount(1, $widget, 'Widget should be present');
        $this->assertEquals('region', $widget->attr('role'));

        // Input should be focusable
        $input = $crawler->filter('[data-tmonier-copilot-target="input"]');
        $this->assertCount(1, $input, 'Input should be present');

        // Submit button should be focusable
        $button = $crawler->filter('[data-tmonier-copilot-target="submitButton"]');
        $this->assertCount(1, $button, 'Submit button should be present');

        // Conversation should have aria-live="polite"
        $conversation = $crawler->filter('[data-tmonier-copilot-target="conversation"]');
        $this->assertCount(1, $conversation, 'Conversation should be present');
        $this->assertEquals('polite', $conversation->attr('aria-live'));

        // The widget posts to the chat route of the current locale (the route is prefixed with /{_locale})
        $this->assertSame('/en_US/api/copilot/chat', $widget->attr('data-tmonier-copilot-api-url-value'));
    }
}
