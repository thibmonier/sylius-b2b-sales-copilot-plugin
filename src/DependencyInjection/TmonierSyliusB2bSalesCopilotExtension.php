<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class TmonierSyliusB2bSalesCopilotExtension extends Extension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);
        $this->prependTwigHooks($container);
    }

    private function prependTwigHooks(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        if (!$container->hasExtension('sylius_twig_hooks')) {
            return;
        }

        $container->prependExtensionConfig('sylius_twig_hooks', [
            'hooks' => [
                'sylius_shop.account.dashboard.index.content.main.section' => [
                    'copilot_widget' => [
                        'template' => '@TmonierSyliusB2bSalesCopilotPlugin/account/copilot_widget.html.twig',
                        'priority' => -100,
                    ],
                ],
            ],
        ]);
    }

    public function getAlias(): string
    {
        return 'tmonier_sylius_b2b_sales_copilot';
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Tmonier\SyliusB2bSalesCopilotPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@TmonierSyliusB2bSalesCopilotPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
