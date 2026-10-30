<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class InstallationTest extends KernelTestCase
{
    public function testTheBundleIsLoaded(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $bundles = $container->getParameter('kernel.bundles');
        self::assertIsArray($bundles);
        self::assertArrayHasKey('TmonierSyliusB2bSalesCopilotPlugin', $bundles);
    }

    public function testTheContainerCompiles(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        // The container is functional if it can resolve services
        self::assertTrue($container->has('kernel'));
    }
}
