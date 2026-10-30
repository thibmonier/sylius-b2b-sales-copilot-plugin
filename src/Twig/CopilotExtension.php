<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Twig;

use Sylius\Component\Channel\Context\ChannelContextInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CopilotExtension extends AbstractExtension
{
    /**
     * @param array<string> $enabledChannels
     */
    public function __construct(
        private readonly ChannelContextInterface $channelContext,
        private readonly array $enabledChannels,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_copilot_enabled', [$this, 'isCopilotEnabled']),
        ];
    }

    public function isCopilotEnabled(): bool
    {
        try {
            $currentChannel = $this->channelContext->getChannel();

            return in_array($currentChannel->getCode(), $this->enabledChannels, true);
        } catch (\Exception) {
            return false;
        }
    }
}
