<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class KeyboardNavigationTest extends WebTestCase
{
    public function testWidgetIsAccessibleByKeyboard(): void
    {
        $client = static::createClient();

        // Create and login a ShopUser
        $container = self::getContainer();
        $shopUserFactory = $container->get('sylius.factory.shop_user');
        $customerFactory = $container->get('sylius.factory.customer');
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $customer = $customerFactory->createNew();
        $customer->setEmail('test@example.com');
        $customer->setFirstName('Test');
        $customer->setLastName('User');

        $shopUser = $shopUserFactory->createNew();
        $shopUser->setUsername('test@example.com');
        $shopUser->setPlainPassword('password123');
        $shopUser->setEnabled(true);
        $shopUser->setCustomer($customer);

        $entityManager->persist($customer);
        $entityManager->persist($shopUser);
        $entityManager->flush();

        $client->loginUser($shopUser, 'shop');

        // Navigate to account dashboard
        $client->request('GET', '/en_US/account/dashboard');

        $this->assertResponseIsSuccessful();

        // Check that the widget has accessible attributes
        $crawler = $client->getCrawler();

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
    }
}
