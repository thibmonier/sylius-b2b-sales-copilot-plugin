<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Functional\Copilot;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
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
}
