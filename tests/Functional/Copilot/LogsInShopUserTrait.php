<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait LogsInShopUserTrait
{
    private function logInNewShopUser(KernelBrowser $client): ShopUser
    {
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        assert($entityManager instanceof EntityManagerInterface);

        $customer = new Customer();
        $customer->setEmail('test_' . uniqid() . '@example.com');
        $customer->setFirstName('Test');
        $customer->setLastName('User');

        $shopUser = new ShopUser();
        $shopUser->setUsername('test_' . uniqid() . '@example.com');
        $shopUser->setPlainPassword('password123');
        $shopUser->setEnabled(true);
        $shopUser->setCustomer($customer);

        $entityManager->persist($customer);
        $entityManager->persist($shopUser);
        $entityManager->flush();

        // The shop firewall of the Sylius TestApplication is named "shop".
        $client->loginUser($shopUser, 'shop');

        return $shopUser;
    }

    /**
     * The Sylius TestApplication only ships the en_US locale: any other locale prefix is redirected
     * to the default one. Enables the given locale on the default channel (rolled back with the test).
     */
    private function enableLocaleOnDefaultChannel(string $localeCode): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        assert($entityManager instanceof EntityManagerInterface);
        $channelRepository = $container->get('sylius.repository.channel');
        assert($channelRepository instanceof ChannelRepositoryInterface);
        $localeRepository = $container->get('sylius.repository.locale');
        assert($localeRepository instanceof RepositoryInterface);
        $localeFactory = $container->get('sylius.factory.locale');
        assert($localeFactory instanceof FactoryInterface);

        $channel = $channelRepository->findOneBy([]);
        assert($channel instanceof Channel);

        $locale = $localeRepository->findOneBy(['code' => $localeCode]);
        if (!$locale instanceof LocaleInterface) {
            $locale = $localeFactory->createNew();
            assert($locale instanceof LocaleInterface);
            $locale->setCode($localeCode);
            $entityManager->persist($locale);
        }

        $channel->addLocale($locale);
        $entityManager->flush();
    }
}
