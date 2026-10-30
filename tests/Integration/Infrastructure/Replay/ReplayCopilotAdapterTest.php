<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Integration\Infrastructure\Replay;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusB2bSalesCopilotPlugin\Infrastructure\Replay\ReplayCopilotAdapter;

final class ReplayCopilotAdapterTest extends TestCase
{
    private string $tempCassettePath;

    protected function setUp(): void
    {
        $this->tempCassettePath = sys_get_temp_dir() . '/test_cassettes_' . uniqid() . '.jsonl';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempCassettePath)) {
            unlink($this->tempCassettePath);
        }
    }

    public function testChatReturnsRecordedResponseForKnownIntent(): void
    {
        // Given a cassette with a recorded response
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'test request',
            'response' => 'Test response from cassette',
            'tools' => [],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the same request
        $response = $adapter->chat('test request', []);

        // Then the recorded response is returned
        self::assertSame('Test response from cassette', $response->message);
        self::assertEmpty($response->tools);
    }

    public function testChatReturnsFallbackMessageForUnknownIntent(): void
    {
        // Given an empty cassette file
        file_put_contents($this->tempCassettePath, '');

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with an unknown request
        $response = $adapter->chat('unknown request', []);

        // Then the fallback message is returned
        self::assertStringContainsString('non enregistré', $response->message);
        self::assertEmpty($response->tools);
    }

    public function testChatNormalizesKeyBeforeMatching(): void
    {
        // Given a cassette with a recorded response in lowercase
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'test request',
            'response' => 'Normalized response',
            'tools' => [],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with different casing and whitespace
        $response = $adapter->chat('  TEST REQUEST  ', []);

        // Then the response is still matched (key is normalized)
        self::assertSame('Normalized response', $response->message);
    }

    public function testChatReturnsToolCallsFromCassette(): void
    {
        // Given a cassette with tool calls
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'reorder request',
            'response' => 'Articles ajoutés au panier',
            'tools' => [
                [
                    'name' => 'reorder',
                    'args' => ['order_number' => '000042'],
                    'result' => 'success',
                ],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the request
        $response = $adapter->chat('reorder request', []);

        // Then the tool calls are returned
        self::assertSame('Articles ajoutés au panier', $response->message);
        self::assertCount(1, $response->tools);
        self::assertSame('reorder', $response->tools[0]->name);
        self::assertSame(['order_number' => '000042'], $response->tools[0]->args);
        self::assertSame('success', $response->tools[0]->result);
    }
}
