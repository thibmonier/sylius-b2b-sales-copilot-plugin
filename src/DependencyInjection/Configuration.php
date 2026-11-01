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
                ->arrayNode('ai')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('Enable real LLM calls via Symfony AI Platform (requires symfony/ai-platform)')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('model')
                            ->info('Model identifier (e.g. claude-sonnet-4-5)')
                            ->defaultValue('claude-sonnet-4-5')
                        ->end()
                        ->scalarNode('system_prompt')
                            ->info('System prompt for the copilot')
                            ->defaultValue('You are a B2B purchasing assistant. Help the buyer manage their cart, search products, and reorder from past orders. Answer in the language of the user.')
                        ->end()
                        ->integerNode('daily_cost_limit_cents')
                            ->info('Maximum daily cost in cents (0 = unlimited)')
                            ->defaultValue(0)
                            ->min(0)
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
