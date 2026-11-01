<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Guard;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusB2bSalesCopilotPlugin\Guard\ToolWhitelist;

final class ToolWhitelistTest extends TestCase
{
    public function testAllowsToolInWhitelist(): void
    {
        $whitelist = new ToolWhitelist(['view_cart', 'search_products', 'list_my_orders']);

        self::assertTrue($whitelist->isAllowed('view_cart'));
        self::assertTrue($whitelist->isAllowed('search_products'));
        self::assertTrue($whitelist->isAllowed('list_my_orders'));
    }

    public function testDeniesToolNotInWhitelist(): void
    {
        $whitelist = new ToolWhitelist(['view_cart', 'search_products']);

        self::assertFalse($whitelist->isAllowed('add_to_cart'));
        self::assertFalse($whitelist->isAllowed('remove_from_cart'));
        self::assertFalse($whitelist->isAllowed('reorder'));
    }

    public function testEmptyWhitelistDeniesAllTools(): void
    {
        $whitelist = new ToolWhitelist([]);

        self::assertFalse($whitelist->isAllowed('view_cart'));
        self::assertFalse($whitelist->isAllowed('any_tool'));
    }

    public function testIsCaseSensitive(): void
    {
        $whitelist = new ToolWhitelist(['view_cart']);

        self::assertTrue($whitelist->isAllowed('view_cart'));
        self::assertFalse($whitelist->isAllowed('VIEW_CART'));
        self::assertFalse($whitelist->isAllowed('View_Cart'));
    }
}
