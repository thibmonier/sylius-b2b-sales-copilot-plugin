<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Replay\ReplayCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;

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
};
