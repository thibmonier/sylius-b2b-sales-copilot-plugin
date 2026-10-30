<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tmonier\SyliusB2bSalesCopilotPlugin\TmonierSyliusB2bSalesCopilotPlugin;

final class InstallationTest extends KernelTestCase
{
    public function testTheBundleIsLoaded(): void
    {
        $kernel = self::bootKernel();
        $bundles = $kernel->getBundles();

        self::assertArrayHasKey(TmonierSyliusB2bSalesCopilotPlugin::class, $bundles);
        self::assertInstanceOf(TmonierSyliusB2bSalesCopilotPlugin::class, $bundles[TmonierSyliusB2bSalesCopilotPlugin::class]);
    }

    public function testTheContainerCompiles(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        self::assertTrue($container->isCompiled());
    }
}
