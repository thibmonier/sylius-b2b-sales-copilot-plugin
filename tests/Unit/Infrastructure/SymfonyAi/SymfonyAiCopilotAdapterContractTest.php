<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Infrastructure\SymfonyAi;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\Result\DeferredResult;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Fallback\FallbackCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\SymfonyAi\SymfonyAiCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotResponse;

/**
 * Port contract of the Symfony AI adapter. When Symfony AI 1.0 lands, this is the test that says what breaks.
 */
final class SymfonyAiCopilotAdapterContractTest extends TestCase
{
    private const SYSTEM_PROMPT = 'You are a B2B copilot.';
    private const QUESTION = 'Quelles références de gants en stock ?';

    public function testReturnsPlatformTextAndForwardsSystemPromptAndQuestion(): void
    {
        $platform = new RecordingPlatform(MockPlatformFactory::createPlatform('3 références trouvées'));
        $adapter = new SymfonyAiCopilotAdapter($platform, 'claude-sonnet-4-5', self::SYSTEM_PROMPT);

        $response = $adapter->chat(self::QUESTION, []);

        self::assertSame('3 références trouvées', $response->message);
        self::assertSame('claude-sonnet-4-5', $platform->model);
        self::assertInstanceOf(MessageBag::class, $platform->input);
        self::assertSame(self::SYSTEM_PROMPT, $platform->input->getSystemMessage()?->getContent());
        self::assertSame(self::QUESTION, $platform->input->getUserMessage()?->asText());
    }

    public function testPlatformFailureReachesFallbackChainWithoutLoggingTheQuestion(): void
    {
        $platform = $this->createMock(PlatformInterface::class);
        $platform->method('invoke')->willThrowException(new \RuntimeException('API error'));
        $logger = new class() extends AbstractLogger {
            /** @var list<string> */
            public array $lines = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->lines[] = $message . ' ' . json_encode($context, \JSON_THROW_ON_ERROR);
            }
        };
        $replay = $this->createMock(CopilotModelInterface::class);
        $replay->method('chat')->willReturn(new CopilotResponse('réponse de repli', []));

        $chain = new FallbackCopilotAdapter(
            new SymfonyAiCopilotAdapter($platform, 'claude-sonnet-4-5', self::SYSTEM_PROMPT),
            $replay,
            $logger,
        );
        $response = $chain->chat(self::QUESTION, []);

        self::assertStringContainsString('réponse de repli', $response->message);
        self::assertNotSame([], $logger->lines);
        self::assertStringNotContainsString('gants', implode("\n", $logger->lines));
    }
}

/** Real PlatformInterface (Symfony AI 0.15) decorator that remembers the last call. */
final class RecordingPlatform implements PlatformInterface
{
    public string|Model|null $model = null;
    public array|string|object|null $input = null;

    public function __construct(private readonly PlatformInterface $inner)
    {
    }

    public function invoke(string|Model $model, array|string|object $input, array $options = []): DeferredResult
    {
        $this->model = $model;
        $this->input = $input;

        return $this->inner->invoke($model, $input, $options);
    }

    public function getModelCatalog(): ModelCatalogInterface
    {
        return $this->inner->getModelCatalog();
    }
}
