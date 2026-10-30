<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services->load('Tmonier\\SyliusB2bSalesCopilotPlugin\\', '../src/')
        ->exclude('../src/{DependencyInjection,Migrations,TmonierSyliusB2bSalesCopilotPlugin.php}');
};
