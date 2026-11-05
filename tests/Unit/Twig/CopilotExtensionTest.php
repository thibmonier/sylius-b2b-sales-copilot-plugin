<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\ChannelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Twig\CopilotExtension;

final class CopilotExtensionTest extends TestCase
{
    private function channelContext(string $code): ChannelContextInterface
    {
        $channel = $this->createStub(ChannelInterface::class);
        $channel->method('getCode')->willReturn($code);
        $context = $this->createStub(ChannelContextInterface::class);
        $context->method('getChannel')->willReturn($channel);

        return $context;
    }

    public function testDeclaresTheIsCopilotEnabledFunction(): void
    {
        $extension = new CopilotExtension($this->channelContext('FASHION_WEB'), []);

        $names = array_map(static fn ($function): string => $function->getName(), $extension->getFunctions());

        self::assertSame(['is_copilot_enabled'], $names);
    }

    public function testCopilotIsEnabledOnAConfiguredChannel(): void
    {
        $extension = new CopilotExtension($this->channelContext('FASHION_WEB'), ['FASHION_WEB']);

        self::assertTrue($extension->isCopilotEnabled());
    }

    public function testCopilotIsDisabledOnAChannelThatIsNotConfigured(): void
    {
        $extension = new CopilotExtension($this->channelContext('OTHER'), ['FASHION_WEB']);

        self::assertFalse($extension->isCopilotEnabled());
    }

    public function testCopilotIsDisabledWhenNoChannelCanBeResolved(): void
    {
        $context = $this->createStub(ChannelContextInterface::class);
        $context->method('getChannel')->willThrowException(new ChannelNotFoundException());

        $extension = new CopilotExtension($context, ['FASHION_WEB']);

        self::assertFalse($extension->isCopilotEnabled());
    }
}
