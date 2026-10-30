<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tmonier\SyliusB2bSalesCopilotPlugin\Controller\CopilotChatController;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Replay\ReplayCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Twig\CopilotExtension;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services->load('Tmonier\\SyliusB2bSalesCopilotPlugin\\', '../src/')
        ->exclude('../src/{DependencyInjection,Migrations,TmonierSyliusB2bSalesCopilotPlugin.php}');

    $services->set(ReplayCopilotAdapter::class)
        ->arg('$cassettePath', __DIR__ . '/cassettes.jsonl');

    $services->alias(CopilotModelInterface::class, ReplayCopilotAdapter::class)
        ->public();

    $services->set(CopilotChatController::class)
        ->public()
        ->tag('controller.service_arguments')
        ->arg('$enabledChannels', param('tmonier_sylius_b2b_sales_copilot.enabled_channels'));

    $services->set(CopilotExtension::class)
        ->arg('$enabledChannels', param('tmonier_sylius_b2b_sales_copilot.enabled_channels'))
        ->tag('twig.extension');
};
