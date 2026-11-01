<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Psr\Log\LoggerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Tmonier\SyliusB2bSalesCopilotPlugin\Audit\CopilotAuditLogger;
use Tmonier\SyliusB2bSalesCopilotPlugin\Controller\CopilotChatController;
use Tmonier\SyliusB2bSalesCopilotPlugin\Guard\CopilotScopeGuard;
use Tmonier\SyliusB2bSalesCopilotPlugin\Guard\ToolWhitelist;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\CostLimit\DailyCostLimiter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Fallback\FallbackCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Replay\ReplayCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\SymfonyAi\SymfonyAiCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\GuardedCopilotModel;
use Tmonier\SyliusB2bSalesCopilotPlugin\RateLimit\CopilotRateLimiter;
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

    $services->set(ToolWhitelist::class)
        ->arg('$allowedTools', param('tmonier_sylius_b2b_sales_copilot.tool_whitelist'));

    $services->set(CopilotScopeGuard::class);

    $services->set(CopilotAuditLogger::class)
        ->arg('$copilotAuditLogger', service('monolog.logger.copilot_audit'));

    $services->set(CopilotRateLimiter::class)
        ->arg('$copilotLimiter', service('limiter.copilot_requests'));

    $services->set(DailyCostLimiter::class)
        ->arg('$cache', service('cache.app'))
        ->arg('$dailyLimitCents', param('tmonier_sylius_b2b_sales_copilot.ai.daily_cost_limit_cents'));

    $services->set(SymfonyAiCopilotAdapter::class)
        ->arg('$platform', service('symfony_ai.platform')->nullOnInvalid())
        ->arg('$model', param('tmonier_sylius_b2b_sales_copilot.ai.model'))
        ->arg('$systemPrompt', param('tmonier_sylius_b2b_sales_copilot.ai.system_prompt'));

    $services->set(FallbackCopilotAdapter::class)
        ->arg('$realAdapter', service(SymfonyAiCopilotAdapter::class))
        ->arg('$replayAdapter', service(ReplayCopilotAdapter::class))
        ->arg('$logger', service(LoggerInterface::class));

    $services->set(GuardedCopilotModel::class)
        ->arg('$decorated', service(ReplayCopilotAdapter::class))
        ->arg('$whitelist', service(ToolWhitelist::class))
        ->arg('$scopeGuard', service(CopilotScopeGuard::class))
        ->arg('$auditLogger', service(CopilotAuditLogger::class))
        ->arg('$rateLimiter', service(CopilotRateLimiter::class));

    $services->alias(CopilotModelInterface::class, GuardedCopilotModel::class)
        ->public();

    $services->set(CopilotChatController::class)
        ->public()
        ->tag('controller.service_arguments')
        ->arg('$enabledChannels', param('tmonier_sylius_b2b_sales_copilot.enabled_channels'));

    $services->set(CopilotExtension::class)
        ->arg('$enabledChannels', param('tmonier_sylius_b2b_sales_copilot.enabled_channels'))
        ->tag('twig.extension');
};
