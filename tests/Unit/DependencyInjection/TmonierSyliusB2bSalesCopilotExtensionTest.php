<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Tmonier\SyliusB2bSalesCopilotPlugin\DependencyInjection\TmonierSyliusB2bSalesCopilotExtension;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Fallback\FallbackCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Replay\ReplayCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\GuardedCopilotModel;

final class TmonierSyliusB2bSalesCopilotExtensionTest extends TestCase
{
    private TmonierSyliusB2bSalesCopilotExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new TmonierSyliusB2bSalesCopilotExtension();
    }

    private function stubExtension(ContainerBuilder $container, string $alias): void
    {
        $container->registerExtension(new class($alias) extends Extension {
            public function __construct(private readonly string $alias)
            {
            }

            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return $this->alias;
            }
        });
    }

    public function testAliasMatchesTheConfigurationRootKey(): void
    {
        self::assertSame('tmonier_sylius_b2b_sales_copilot', $this->extension->getAlias());
    }

    public function testLoadExposesTheDefaultsAsParameters(): void
    {
        $container = new ContainerBuilder();

        $this->extension->load([], $container);

        self::assertSame([], $container->getParameter('tmonier_sylius_b2b_sales_copilot.enabled_channels'));
        self::assertSame(50, $container->getParameter('tmonier_sylius_b2b_sales_copilot.daily_limit'));
        self::assertFalse($container->getParameter('tmonier_sylius_b2b_sales_copilot.ai.enabled'));
        self::assertSame('claude-sonnet-4-5', $container->getParameter('tmonier_sylius_b2b_sales_copilot.ai.model'));
        self::assertSame(0, $container->getParameter('tmonier_sylius_b2b_sales_copilot.ai.daily_cost_limit_cents'));
        self::assertContains('reorder', $container->getParameter('tmonier_sylius_b2b_sales_copilot.tool_whitelist'));
    }

    public function testLoadExposesTheConfiguredValuesAsParameters(): void
    {
        $container = new ContainerBuilder();

        $this->extension->load([[
            'enabled_channels' => ['FASHION_WEB'],
            'tool_whitelist' => ['view_cart'],
            'daily_limit' => 7,
            'ai' => ['model' => 'claude-haiku-5-5', 'system_prompt' => 'Be brief.', 'daily_cost_limit_cents' => 250],
        ]], $container);

        self::assertSame(['FASHION_WEB'], $container->getParameter('tmonier_sylius_b2b_sales_copilot.enabled_channels'));
        self::assertSame(['view_cart'], $container->getParameter('tmonier_sylius_b2b_sales_copilot.tool_whitelist'));
        self::assertSame(7, $container->getParameter('tmonier_sylius_b2b_sales_copilot.daily_limit'));
        self::assertSame('claude-haiku-5-5', $container->getParameter('tmonier_sylius_b2b_sales_copilot.ai.model'));
        self::assertSame('Be brief.', $container->getParameter('tmonier_sylius_b2b_sales_copilot.ai.system_prompt'));
        self::assertSame(250, $container->getParameter('tmonier_sylius_b2b_sales_copilot.ai.daily_cost_limit_cents'));
    }

    public function testLoadRegistersTheCopilotModelAliasOnTheGuardedModel(): void
    {
        $container = new ContainerBuilder();

        $this->extension->load([], $container);

        self::assertSame(GuardedCopilotModel::class, (string) $container->getAlias(CopilotModelInterface::class));
        self::assertTrue($container->getAlias(CopilotModelInterface::class)->isPublic());
    }

    public function testGuardedModelDecoratesTheReplayAdapterWhileAiIsDisabled(): void
    {
        $container = new ContainerBuilder();

        $this->extension->load([], $container);

        $decorated = $container->getDefinition(GuardedCopilotModel::class)->getArgument('$decorated');
        self::assertInstanceOf(Reference::class, $decorated);
        self::assertSame(ReplayCopilotAdapter::class, (string) $decorated);
    }

    public function testGuardedModelDecoratesTheFallbackChainWhenAiIsEnabled(): void
    {
        $container = new ContainerBuilder();

        $this->extension->load([['ai' => ['enabled' => true]]], $container);

        $decorated = $container->getDefinition(GuardedCopilotModel::class)->getArgument('$decorated');
        self::assertInstanceOf(Reference::class, $decorated);
        self::assertSame(FallbackCopilotAdapter::class, (string) $decorated);
    }

    public function testPrependRegistersTheAccountDashboardHookWhenTwigHooksIsAvailable(): void
    {
        $container = new ContainerBuilder();
        $this->stubExtension($container, 'sylius_twig_hooks');

        $this->extension->prepend($container);

        $hooks = $container->getExtensionConfig('sylius_twig_hooks')[0]['hooks'];
        $widget = $hooks['sylius_shop.account.dashboard.index.content.main.section']['copilot_widget'];
        self::assertSame('@TmonierSyliusB2bSalesCopilotPlugin/account/copilot_widget.html.twig', $widget['template']);
        self::assertSame(-100, $widget['priority']);
    }

    public function testPrependConfiguresTheDailyFixedWindowRateLimiter(): void
    {
        $container = new ContainerBuilder();
        $this->stubExtension($container, 'framework');
        $container->registerExtension($this->extension);
        $container->loadFromExtension('tmonier_sylius_b2b_sales_copilot', ['daily_limit' => 12]);

        $this->extension->prepend($container);

        $limiter = $container->getExtensionConfig('framework')[0]['rate_limiter']['copilot_requests'];
        self::assertSame(['policy' => 'fixed_window', 'limit' => 12, 'interval' => '1 day'], $limiter);
    }

    public function testPrependConfiguresTheAuditLogChannelAndHandler(): void
    {
        $container = new ContainerBuilder();
        $this->stubExtension($container, 'monolog');

        $this->extension->prepend($container);

        $monolog = $container->getExtensionConfig('monolog')[0];
        self::assertSame(['copilot_audit'], $monolog['channels']);
        self::assertSame('%kernel.logs_dir%/copilot_audit.log', $monolog['handlers']['copilot_audit']['path']);
        self::assertSame(['copilot_audit'], $monolog['handlers']['copilot_audit']['channels']);
    }

    public function testPrependRegistersTheMigrationsNamespaceAfterSyliusCoreMigrations(): void
    {
        $container = new ContainerBuilder();
        $this->stubExtension($container, 'doctrine_migrations');
        $this->stubExtension($container, 'sylius_labs_doctrine_migrations_extra');

        $this->extension->prepend($container);

        $paths = $container->getExtensionConfig('doctrine_migrations')[0]['migrations_paths'];
        self::assertSame(
            ['Tmonier\SyliusB2bSalesCopilotPlugin\Migrations' => '@TmonierSyliusB2bSalesCopilotPlugin/src/Migrations'],
            $paths,
        );
        $extra = $container->getExtensionConfig('sylius_labs_doctrine_migrations_extra')[0]['migrations'];
        self::assertSame(
            ['Tmonier\SyliusB2bSalesCopilotPlugin\Migrations' => ['Sylius\Bundle\CoreBundle\Migrations']],
            $extra,
        );
    }

    public function testPrependLeavesOtherBundlesUntouchedWhenTheyAreNotInstalled(): void
    {
        $container = new ContainerBuilder();

        $this->extension->prepend($container);

        self::assertSame([], $container->getExtensionConfig('framework'));
        self::assertSame([], $container->getExtensionConfig('monolog'));
    }
}
