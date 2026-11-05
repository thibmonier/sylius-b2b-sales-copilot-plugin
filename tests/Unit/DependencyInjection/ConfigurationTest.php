<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Tmonier\SyliusB2bSalesCopilotPlugin\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }

    public function testDefaultsDisableTheCopilotOnEveryChannelAndTheRealLlm(): void
    {
        $config = $this->process([]);

        self::assertSame([], $config['enabled_channels']);
        self::assertSame(50, $config['daily_limit']);
        self::assertFalse($config['ai']['enabled']);
        self::assertSame('claude-sonnet-4-5', $config['ai']['model']);
        self::assertSame(0, $config['ai']['daily_cost_limit_cents']);
    }

    public function testDefaultToolWhitelistContainsTheEightCartTools(): void
    {
        $config = $this->process([]);

        self::assertSame([
            'view_cart',
            'get_cart',
            'search_products',
            'list_my_orders',
            'add_to_cart',
            'remove_from_cart',
            'update_cart_quantity',
            'reorder',
        ], $config['tool_whitelist']);
    }

    public function testDefaultSystemPromptAsksToAnswerInTheLanguageOfTheUser(): void
    {
        $config = $this->process([]);

        self::assertStringContainsString('Answer in the language of the user.', $config['ai']['system_prompt']);
    }

    public function testExplicitValuesOverrideTheDefaults(): void
    {
        $config = $this->process([
            'enabled_channels' => ['FASHION_WEB'],
            'tool_whitelist' => ['view_cart'],
            'daily_limit' => 5,
            'ai' => ['enabled' => true, 'model' => 'claude-haiku-5-5', 'system_prompt' => 'Be brief.', 'daily_cost_limit_cents' => 300],
        ]);

        self::assertSame(['FASHION_WEB'], $config['enabled_channels']);
        self::assertSame(['view_cart'], $config['tool_whitelist']);
        self::assertSame(5, $config['daily_limit']);
        self::assertSame(
            ['enabled' => true, 'model' => 'claude-haiku-5-5', 'system_prompt' => 'Be brief.', 'daily_cost_limit_cents' => 300],
            $config['ai'],
        );
    }

    public function testDailyLimitBelowOneIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value 0 is too small for path "tmonier_sylius_b2b_sales_copilot.daily_limit". Should be greater than or equal to 1');

        $this->process(['daily_limit' => 0]);
    }

    public function testNegativeDailyCostLimitIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value -1 is too small for path "tmonier_sylius_b2b_sales_copilot.ai.daily_cost_limit_cents". Should be greater than or equal to 0');

        $this->process(['ai' => ['daily_cost_limit_cents' => -1]]);
    }

    public function testUnknownOptionIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "unknown" under "tmonier_sylius_b2b_sales_copilot"');

        $this->process(['unknown' => true]);
    }
}
