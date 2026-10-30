<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class B2CVisitorTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    /** @var ChannelRepositoryInterface<Channel> */
    private ChannelRepositoryInterface $channelRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        assert($entityManager instanceof EntityManagerInterface);
        $this->entityManager = $entityManager;

        $channelRepository = $container->get('sylius.repository.channel');
        assert($channelRepository instanceof ChannelRepositoryInterface);
        $this->channelRepository = $channelRepository;
    }

    public function testUnauthenticatedUserCannotAccessCopilotApi(): void
    {
        // Try to access the API without authentication
        $this->client->request('POST', '/en_US/api/copilot/chat', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'message' => 'état de mon panier',
        ], \JSON_THROW_ON_ERROR));

        // Currently returns 200 (guard missing), should return 302/401/403
        $this->assertNotEquals(
            200,
            $this->client->getResponse()->getStatusCode(),
            'Unauthenticated request should NOT return 200 (currently does, guard missing)',
        );
    }

    public function testWidgetIsNotPresentOnPublicPages(): void
    {
        // Check that widget is not present on public pages
        $this->client->request('GET', '/en_US/');

        $this->assertResponseIsSuccessful();

        $crawler = $this->client->getCrawler();
        $widget = $crawler->filter('[data-controller*="copilot"]');
        $this->assertCount(0, $widget, 'Widget should not be present on public pages');
    }

    public function testAuthenticatedUserOnDisabledChannelCannotAccessCopilotApi(): void
    {
        // Get base currency and locale from existing channel
        $defaultChannel = $this->channelRepository->findOneBy([]);
        assert($defaultChannel instanceof Channel);

        // Create a B2C channel (not in enabled_channels list)
        $b2cChannel = new Channel();
        $b2cChannel->setCode('B2C_' . uniqid());
        $b2cChannel->setName('B2C Channel');
        $b2cChannel->setHostname('b2c.localhost');
        $b2cChannel->setEnabled(true);
        $b2cChannel->setBaseCurrency($defaultChannel->getBaseCurrency());
        $b2cChannel->setDefaultLocale($defaultChannel->getDefaultLocale());
        $this->entityManager->persist($b2cChannel);

        // Create and authenticate a user
        $customer = new Customer();
        $customer->setEmail('b2c_' . uniqid() . '@example.com');
        $customer->setFirstName('B2C');
        $customer->setLastName('Customer');

        $shopUser = new ShopUser();
        $shopUser->setUsername('b2c_' . uniqid() . '@example.com');
        $shopUser->setPlainPassword('password123');
        $shopUser->setEnabled(true);
        $shopUser->setCustomer($customer);

        $this->entityManager->persist($customer);
        $this->entityManager->persist($shopUser);
        $this->entityManager->flush();

        $this->client->loginUser($shopUser, 'shop');

        // Request on B2C channel (via hostname)
        $this->client->request('POST', '/en_US/api/copilot/chat', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_HOST' => 'b2c.localhost',
        ], json_encode([
            'message' => 'état de mon panier',
        ], \JSON_THROW_ON_ERROR));

        // Should return 403 (channel not in enabled_channels list)
        $this->assertEquals(
            403,
            $this->client->getResponse()->getStatusCode(),
            'Authenticated request on disabled channel should return 403 (currently does not, channel guard missing)',
        );
    }

    public function testWidgetNotPresentOnDisabledChannel(): void
    {
        // Get base currency and locale from existing channel
        $defaultChannel = $this->channelRepository->findOneBy([]);
        assert($defaultChannel instanceof Channel);

        // Create a B2C channel
        $b2cChannel = new Channel();
        $b2cChannel->setCode('B2C_' . uniqid());
        $b2cChannel->setName('B2C Channel');
        $b2cChannel->setHostname('b2c.localhost');
        $b2cChannel->setEnabled(true);
        $b2cChannel->setBaseCurrency($defaultChannel->getBaseCurrency());
        $b2cChannel->setDefaultLocale($defaultChannel->getDefaultLocale());
        $this->entityManager->persist($b2cChannel);

        // Create and authenticate a user
        $customer = new Customer();
        $customer->setEmail('b2c2_' . uniqid() . '@example.com');
        $customer->setFirstName('B2C');
        $customer->setLastName('Customer');

        $shopUser = new ShopUser();
        $shopUser->setUsername('b2c2_' . uniqid() . '@example.com');
        $shopUser->setPlainPassword('password123');
        $shopUser->setEnabled(true);
        $shopUser->setCustomer($customer);

        $this->entityManager->persist($customer);
        $this->entityManager->persist($shopUser);
        $this->entityManager->flush();

        $this->client->loginUser($shopUser, 'shop');

        // Request dashboard on B2C channel
        $this->client->request('GET', '/en_US/account/dashboard', [], [], [
            'HTTP_HOST' => 'b2c.localhost',
        ]);

        $this->assertResponseIsSuccessful();

        // Widget should NOT be present on disabled channel
        $crawler = $this->client->getCrawler();
        $widget = $crawler->filter('[data-controller="tmonier-copilot"]');
        $this->assertCount(0, $widget, 'Widget should not be present on disabled channel (currently is, conditional rendering missing)');
    }
}
