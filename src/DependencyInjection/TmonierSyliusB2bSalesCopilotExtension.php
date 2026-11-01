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
use Symfony\Component\DependencyInjection\Reference;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Fallback\FallbackCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\GuardedCopilotModel;

final class TmonierSyliusB2bSalesCopilotExtension extends Extension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $enabledChannels = $config['enabled_channels'] ?? [];
        assert(is_array($enabledChannels));
        $container->setParameter('tmonier_sylius_b2b_sales_copilot.enabled_channels', $enabledChannels);

        $toolWhitelist = $config['tool_whitelist'] ?? [];
        assert(is_array($toolWhitelist));
        $container->setParameter('tmonier_sylius_b2b_sales_copilot.tool_whitelist', $toolWhitelist);

        $dailyLimit = $config['daily_limit'] ?? 50;
        assert(is_int($dailyLimit));
        $container->setParameter('tmonier_sylius_b2b_sales_copilot.daily_limit', $dailyLimit);

        $ai = $config['ai'] ?? [];
        assert(is_array($ai));
        $container->setParameter('tmonier_sylius_b2b_sales_copilot.ai.enabled', $ai['enabled'] ?? false);
        $container->setParameter('tmonier_sylius_b2b_sales_copilot.ai.model', $ai['model'] ?? 'claude-sonnet-4-5');
        $container->setParameter('tmonier_sylius_b2b_sales_copilot.ai.system_prompt', $ai['system_prompt'] ?? '');
        $container->setParameter('tmonier_sylius_b2b_sales_copilot.ai.daily_cost_limit_cents', $ai['daily_cost_limit_cents'] ?? 0);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        if ($ai['enabled'] ?? false) {
            $container->getDefinition(GuardedCopilotModel::class)
                ->replaceArgument('$decorated', new Reference(FallbackCopilotAdapter::class));
        }
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
