<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Architecture;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Fallback\FallbackCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Replay\ReplayCopilotAdapter;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\CopilotModelInterface;

/**
 * Symfony AI is allowed in src/Infrastructure/SymfonyAi/ only, so that moving to 1.0 means adapting that folder.
 */
final class SymfonyAiConfinementTest extends TestCase
{
    public function testNoSymfonyAiClassIsReferencedOutsideTheAdapterFolder(): void
    {
        $root = \dirname(__DIR__, 3);
        $allowed = $root . '/src/Infrastructure/SymfonyAi/';
        $leaks = [];

        foreach (['src', 'config'] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/' . $dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $path = $file->getPathname();
                if (!str_ends_with($path, '.php') || str_starts_with($path, $allowed)) {
                    continue;
                }
                if (1 === preg_match('/Symfony\\\\+AI\\\\/', (string) file_get_contents($path))) {
                    $leaks[] = substr($path, \strlen($root) + 1);
                }
            }
        }

        self::assertSame([], $leaks, 'Symfony AI must stay inside src/Infrastructure/SymfonyAi/');
    }

    #[RunInSeparateProcess]
    public function testReplayModeLoadsNoSymfonyAiClass(): void
    {
        $chain = new FallbackCopilotAdapter(
            new ReplayCopilotAdapter(\dirname(__DIR__, 3) . '/config/cassettes.jsonl'),
            new ReplayCopilotAdapter(\dirname(__DIR__, 3) . '/config/cassettes.jsonl'),
            new NullLogger(),
        );
        self::assertInstanceOf(CopilotModelInterface::class, $chain);
        $chain->chat('état de mon panier', []);

        $loaded = array_filter(
            array_merge(get_declared_classes(), get_declared_interfaces()),
            static fn (string $class): bool => str_starts_with($class, 'Symfony\\AI\\'),
        );

        self::assertSame([], array_values($loaded));
    }
}
