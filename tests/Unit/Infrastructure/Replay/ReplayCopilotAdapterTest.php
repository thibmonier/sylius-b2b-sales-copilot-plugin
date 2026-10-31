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

        // When chatting with an unknown request in French (contains "ma" → detected as French)
        $response = $adapter->chat('ma demande inconnue', []);

        // Then the fallback message is returned in French
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

    public function testChatMatchesKnownAlias(): void
    {
        // Given a cassette with aliases
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'état de mon panier',
            'aliases' => [
                'montre-moi mon panier',
                'affiche mon panier',
                'consulter mon panier',
            ],
            'response' => 'Votre panier contient 3 articles.',
            'tools' => [],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with an alias
        $response = $adapter->chat('montre-moi mon panier', []);

        // Then the cassette response is returned
        self::assertSame('Votre panier contient 3 articles.', $response->message);
    }

    public function testChatMatchesByKeywordsWhenExactMatchAndAliasFail(): void
    {
        // Given a cassette with keywords in the intent
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'recommande comme le mois dernier',
            'aliases' => [],
            'response' => 'Commande retrouvée.',
            'tools' => [],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with similar keywords but not exact match
        $response = $adapter->chat('peux-tu recommander le mois dernier', []);

        // Then the cassette response is returned (matched by keywords)
        self::assertSame('Commande retrouvée.', $response->message);
    }

    public function testChatFallbackMessageListsAvailableScenariosInFrench(): void
    {
        // Given cassettes in French and English
        $cassettes = [
            json_encode([
                'version' => '1.0',
                'intent' => 'état de mon panier',
                'aliases' => ['montre mon panier'],
                'response' => 'Panier FR',
                'tools' => [],
            ]),
            json_encode([
                'version' => '1.0',
                'intent' => "what's in my cart",
                'aliases' => ['show my cart'],
                'response' => 'Cart EN',
                'tools' => [],
            ]),
        ];
        file_put_contents($this->tempCassettePath, implode("\n", $cassettes) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with unknown request in French
        $response = $adapter->chat('commande introuvable', []);

        // Then the fallback suggests French scenarios
        self::assertStringContainsString('Scénarios disponibles', $response->message);
        self::assertStringContainsString('état de mon panier', $response->message);
    }

    public function testChatFallbackMessageListsAvailableScenariosInEnglish(): void
    {
        // Given cassettes in French and English
        $cassettes = [
            json_encode([
                'version' => '1.0',
                'intent' => 'état de mon panier',
                'aliases' => ['montre mon panier'],
                'response' => 'Panier FR',
                'tools' => [],
            ]),
            json_encode([
                'version' => '1.0',
                'intent' => "what's in my cart",
                'aliases' => ['show my cart'],
                'response' => 'Cart EN',
                'tools' => [],
            ]),
        ];
        file_put_contents($this->tempCassettePath, implode("\n", $cassettes) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with unknown request in English
        $response = $adapter->chat('unknown command', []);

        // Then the fallback suggests English scenarios
        self::assertStringContainsString('Available scenarios', $response->message);
        self::assertStringContainsString("what's in my cart", $response->message);
    }

    public function testQuickOrderAddFrReturnsAddToCartTool(): void
    {
        // Given a cassette with quick_order_add intent (FR)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'ajoute la référence PB-GANT-NITR-09 au panier',
            'response' => 'Référence PB-GANT-NITR-09 ajoutée au panier (1 unité).',
            'tools' => [
                [
                    'name' => 'add_to_cart',
                    'args' => ['reference' => 'PB-GANT-NITR-09', 'quantity' => 1],
                    'result' => 'success',
                ],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the quick order request
        $response = $adapter->chat('ajoute la référence PB-GANT-NITR-09 au panier', []);

        // Then the tool call is returned
        self::assertStringContainsString('PB-GANT-NITR-09', $response->message);
        self::assertCount(1, $response->tools);
        self::assertSame('add_to_cart', $response->tools[0]->name);
        self::assertSame(['reference' => 'PB-GANT-NITR-09', 'quantity' => 1], $response->tools[0]->args);
        self::assertSame('success', $response->tools[0]->result);
    }

    public function testQuickOrderAddEnReturnsAddToCartTool(): void
    {
        // Given a cassette with quick_order_add intent (EN)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'add reference PB-GANT-NITR-09 to cart',
            'response' => 'Reference PB-GANT-NITR-09 added to cart (1 unit).',
            'tools' => [
                [
                    'name' => 'add_to_cart',
                    'args' => ['reference' => 'PB-GANT-NITR-09', 'quantity' => 1],
                    'result' => 'success',
                ],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the quick order request
        $response = $adapter->chat('add reference PB-GANT-NITR-09 to cart', []);

        // Then the tool call is returned
        self::assertStringContainsString('PB-GANT-NITR-09', $response->message);
        self::assertCount(1, $response->tools);
        self::assertSame('add_to_cart', $response->tools[0]->name);
        self::assertSame(['reference' => 'PB-GANT-NITR-09', 'quantity' => 1], $response->tools[0]->args);
        self::assertSame('success', $response->tools[0]->result);
    }

    public function testBatchAddFrReturnsMultipleToolCalls(): void
    {
        // Given a cassette with batch add intent (FR)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'ajoute 5 × PB-GANT-NITR-09 et 3 × EC-BOUCH-MOUS-50 au panier',
            'response' => '2 références ajoutées au panier : 5 × PB-GANT-NITR-09 et 3 × EC-BOUCH-MOUS-50.',
            'tools' => [
                [
                    'name' => 'add_to_cart',
                    'args' => ['reference' => 'PB-GANT-NITR-09', 'quantity' => 5],
                    'result' => 'success',
                ],
                [
                    'name' => 'add_to_cart',
                    'args' => ['reference' => 'EC-BOUCH-MOUS-50', 'quantity' => 3],
                    'result' => 'success',
                ],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the batch add request
        $response = $adapter->chat('ajoute 5 × PB-GANT-NITR-09 et 3 × EC-BOUCH-MOUS-50 au panier', []);

        // Then two tool calls are returned
        self::assertStringContainsString('2 références', $response->message);
        self::assertCount(2, $response->tools);
        self::assertSame('add_to_cart', $response->tools[0]->name);
        self::assertSame(['reference' => 'PB-GANT-NITR-09', 'quantity' => 5], $response->tools[0]->args);
        self::assertSame('add_to_cart', $response->tools[1]->name);
        self::assertSame(['reference' => 'EC-BOUCH-MOUS-50', 'quantity' => 3], $response->tools[1]->args);
    }

    public function testBatchAddEnReturnsMultipleToolCalls(): void
    {
        // Given a cassette with batch add intent (EN)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'add 5 × PB-GANT-NITR-09 and 3 × EC-BOUCH-MOUS-50 to cart',
            'response' => '2 references added to cart: 5 × PB-GANT-NITR-09 and 3 × EC-BOUCH-MOUS-50.',
            'tools' => [
                [
                    'name' => 'add_to_cart',
                    'args' => ['reference' => 'PB-GANT-NITR-09', 'quantity' => 5],
                    'result' => 'success',
                ],
                [
                    'name' => 'add_to_cart',
                    'args' => ['reference' => 'EC-BOUCH-MOUS-50', 'quantity' => 3],
                    'result' => 'success',
                ],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with the batch add request
        $response = $adapter->chat('add 5 × PB-GANT-NITR-09 and 3 × EC-BOUCH-MOUS-50 to cart', []);

        // Then two tool calls are returned
        self::assertStringContainsString('2 references', $response->message);
        self::assertCount(2, $response->tools);
        self::assertSame('add_to_cart', $response->tools[0]->name);
        self::assertSame(['reference' => 'PB-GANT-NITR-09', 'quantity' => 5], $response->tools[0]->args);
        self::assertSame('add_to_cart', $response->tools[1]->name);
        self::assertSame(['reference' => 'EC-BOUCH-MOUS-50', 'quantity' => 3], $response->tools[1]->args);
    }

    public function testEnrichedGetCartFrContainsItemDetails(): void
    {
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'état de mon panier',
            'response' => 'Votre panier contient **47 articles** pour un total de **963,50 € HT**. Top 3 : Gants nitrile (12 unités à 8,50 €), Bouchons oreille (50 unités à 0,45 €), Câble réseau Cat6 (25 unités à 12,00 €). Voulez-vous passer au checkout ?',
            'tools' => [
                ['name' => 'get_cart', 'args' => [], 'result' => 'success'],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);
        $response = $adapter->chat('état de mon panier', []);

        self::assertStringContainsString('47 articles', $response->message);
        self::assertStringContainsString('963,50', $response->message);
        self::assertStringContainsString('Gants nitrile', $response->message);
        self::assertStringContainsString('12 unités', $response->message);
        self::assertCount(1, $response->tools);
        self::assertSame('get_cart', $response->tools[0]->name);
    }

    public function testEnrichedGetCartEnContainsItemDetails(): void
    {
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => "what's in my cart",
            'response' => 'Your cart contains **47 items** for a total of **€963.50 excl. tax**. Top 3: Nitrile gloves (12 units at €8.50), Ear plugs (50 units at €0.45), Cat6 network cable (25 units at €12.00). Would you like to proceed to checkout?',
            'tools' => [
                ['name' => 'get_cart', 'args' => [], 'result' => 'success'],
            ],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);
        $response = $adapter->chat("what's in my cart", []);

        self::assertStringContainsString('47 items', $response->message);
        self::assertStringContainsString('963.50', $response->message);
        self::assertStringContainsString('Nitrile gloves', $response->message);
        self::assertStringContainsString('12 units', $response->message);
        self::assertCount(1, $response->tools);
        self::assertSame('get_cart', $response->tools[0]->name);
    }

    public function testUnknownRefFrReturnsErrorMessage(): void
    {
        // Given a cassette with unknown reference error (FR)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'ajoute INEXISTANT-999 au panier',
            'response' => 'Référence INEXISTANT-999 introuvable dans le catalogue.',
            'tools' => [],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with unknown reference request
        $response = $adapter->chat('ajoute INEXISTANT-999 au panier', []);

        // Then the error message is returned
        self::assertStringContainsString('INEXISTANT-999 introuvable', $response->message);
        self::assertEmpty($response->tools);
    }

    public function testUnknownRefEnReturnsErrorMessage(): void
    {
        // Given a cassette with unknown reference error (EN)
        file_put_contents($this->tempCassettePath, json_encode([
            'version' => '1.0',
            'intent' => 'add INEXISTANT-999 to cart',
            'response' => 'Reference INEXISTANT-999 not found in catalog.',
            'tools' => [],
        ]) . "\n");

        $adapter = new ReplayCopilotAdapter($this->tempCassettePath);

        // When chatting with unknown reference request
        $response = $adapter->chat('add INEXISTANT-999 to cart', []);

        // Then the error message is returned
        self::assertStringContainsString('INEXISTANT-999 not found', $response->message);
        self::assertEmpty($response->tools);
    }
}
