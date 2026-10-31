<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Infrastructure\Replay;

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

    public function testChatReturnsReorderLastMonthCassetteInFrench(): void
    {
        // Given a cassette with reorder last month intent (FR)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'recommande comme le mois dernier',
            'response' => 'J\'ai retrouvé votre commande n° 42 du mois dernier (5 articles). Tous les articles sont disponibles. Je les ai ajoutés à votre panier.',
            'tools' => [
                [
                    'name' => 'list_my_orders',
                    'args' => ['limit' => 1],
                    'result' => 'success',
                ],
                [
                    'name' => 'reorder',
                    'args' => ['order_number' => '000042'],
                    'result' => 'success',
                ],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the French reorder request
        $response = $adapter->chat('recommande comme le mois dernier', []);

        // Then the response contains the expected message and tools
        self::assertStringContainsString('commande n° 42', $response->message);
        self::assertCount(2, $response->tools);
        self::assertSame('list_my_orders', $response->tools[0]->name);
        self::assertSame('reorder', $response->tools[1]->name);
        self::assertSame(['order_number' => '000042'], $response->tools[1]->args);
    }

    public function testChatReturnsReorderLastMonthCassetteInEnglish(): void
    {
        // Given a cassette with reorder last month intent (EN)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'reorder like last month',
            'response' => 'I found your order #42 from last month (5 items). All items are available. I\'ve added them to your cart.',
            'tools' => [
                [
                    'name' => 'list_my_orders',
                    'args' => ['limit' => 1],
                    'result' => 'success',
                ],
                [
                    'name' => 'reorder',
                    'args' => ['order_number' => '000042'],
                    'result' => 'success',
                ],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the English reorder request
        $response = $adapter->chat('reorder like last month', []);

        // Then the response contains the expected message and tools
        self::assertStringContainsString('order #42', $response->message);
        self::assertCount(2, $response->tools);
        self::assertSame('list_my_orders', $response->tools[0]->name);
        self::assertSame('reorder', $response->tools[1]->name);
    }

    public function testChatReturnsReorderWithSubstitutionCassetteInFrench(): void
    {
        // Given a cassette with substitution scenario (FR)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'recommande avec substitution',
            'response' => 'Commande n° 42 retrouvée (5 articles). 4 articles ajoutés au panier. 1 référence inactive : je propose le Câble réseau Cat6 à la place.',
            'tools' => [
                [
                    'name' => 'list_my_orders',
                    'args' => ['limit' => 1],
                    'result' => 'success',
                ],
                [
                    'name' => 'reorder',
                    'args' => ['order_number' => '000042', 'substitutions' => true],
                    'result' => 'partial',
                ],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the substitution request
        $response = $adapter->chat('recommande avec substitution', []);

        // Then the response contains substitution details
        self::assertStringContainsString('4 articles ajoutés', $response->message);
        self::assertStringContainsString('1 référence inactive', $response->message);
        self::assertCount(2, $response->tools);
        self::assertSame('partial', $response->tools[1]->result);
    }

    public function testChatReturnsReorderWithSubstitutionCassetteInEnglish(): void
    {
        // Given a cassette with substitution scenario (EN)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'reorder with substitution',
            'response' => 'Found order #42 (5 items). 4 items added to cart. 1 inactive reference: I suggest Cat6 Network Cable instead.',
            'tools' => [
                [
                    'name' => 'list_my_orders',
                    'args' => ['limit' => 1],
                    'result' => 'success',
                ],
                [
                    'name' => 'reorder',
                    'args' => ['order_number' => '000042', 'substitutions' => true],
                    'result' => 'partial',
                ],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the substitution request
        $response = $adapter->chat('reorder with substitution', []);

        // Then the response contains substitution details
        self::assertStringContainsString('4 items added', $response->message);
        self::assertStringContainsString('1 inactive reference', $response->message);
        self::assertCount(2, $response->tools);
        self::assertSame('partial', $response->tools[1]->result);
    }
}
