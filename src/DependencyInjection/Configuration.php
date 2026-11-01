<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tmonier_sylius_b2b_sales_copilot');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('enabled_channels')
                    ->info('List of channel codes where the copilot is enabled')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('tool_whitelist')
                    ->info('List of allowed tool names')
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        'view_cart',
                        'get_cart',
                        'search_products',
                        'list_my_orders',
                        'add_to_cart',
                        'remove_from_cart',
                        'update_cart_quantity',
                        'reorder',
                    ])
                ->end()
                ->integerNode('daily_limit')
                    ->info('Maximum number of requests per member per day')
                    ->defaultValue(50)
                    ->min(1)
                ->end()
            ->end();

        return $treeBuilder;
    }
}
