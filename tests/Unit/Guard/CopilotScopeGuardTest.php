<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusB2bSalesCopilotPlugin\Unit\Guard;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusB2bSalesCopilotPlugin\Guard\CopilotScopeGuard;
use Tmonier\SyliusB2bSalesCopilotPlugin\Model\ToolCall;

final class CopilotScopeGuardTest extends TestCase
{
    private CopilotScopeGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new CopilotScopeGuard();
    }

    public function testAllowsReadToolsWithReadScope(): void
    {
        $context = ['member_scopes' => ['b2b:read']];

        $viewTool = new ToolCall('view_cart', [], 'result');
        $searchTool = new ToolCall('search_products', [], 'result');
        $listTool = new ToolCall('list_my_orders', [], 'result');

        self::assertTrue($this->guard->canExecuteTool($viewTool, $context));
        self::assertTrue($this->guard->canExecuteTool($searchTool, $context));
        self::assertTrue($this->guard->canExecuteTool($listTool, $context));
    }

    public function testDeniesReadToolsWithoutReadScope(): void
    {
        $context = ['member_scopes' => []];

        $viewTool = new ToolCall('view_cart', [], 'result');

        self::assertFalse($this->guard->canExecuteTool($viewTool, $context));
    }

    public function testAllowsModificationToolsWithCartScope(): void
    {
        $context = ['member_scopes' => ['b2b:cart']];

        $addTool = new ToolCall('add_to_cart', [], 'result');
        $removeTool = new ToolCall('remove_from_cart', [], 'result');
        $updateTool = new ToolCall('update_cart_quantity', [], 'result');
        $reorderTool = new ToolCall('reorder', [], 'result');

        self::assertTrue($this->guard->canExecuteTool($addTool, $context));
        self::assertTrue($this->guard->canExecuteTool($removeTool, $context));
        self::assertTrue($this->guard->canExecuteTool($updateTool, $context));
        self::assertTrue($this->guard->canExecuteTool($reorderTool, $context));
    }

    public function testDeniesModificationToolsWithoutCartScope(): void
    {
        $context = ['member_scopes' => ['b2b:read']];

        $addTool = new ToolCall('add_to_cart', [], 'result');
        $removeTool = new ToolCall('remove_from_cart', [], 'result');

        self::assertFalse($this->guard->canExecuteTool($addTool, $context));
        self::assertFalse($this->guard->canExecuteTool($removeTool, $context));
    }

    public function testDeniesToolWhenScopesNotProvided(): void
    {
        $context = [];

        $viewTool = new ToolCall('view_cart', [], 'result');
        $addTool = new ToolCall('add_to_cart', [], 'result');

        self::assertFalse($this->guard->canExecuteTool($viewTool, $context));
        self::assertFalse($this->guard->canExecuteTool($addTool, $context));
    }

    public function testAllowsToolsWhenMultipleScopesProvided(): void
    {
        $context = ['member_scopes' => ['b2b:read', 'b2b:cart']];

        $viewTool = new ToolCall('view_cart', [], 'result');
        $addTool = new ToolCall('add_to_cart', [], 'result');

        self::assertTrue($this->guard->canExecuteTool($viewTool, $context));
        self::assertTrue($this->guard->canExecuteTool($addTool, $context));
    }

    public function testAllowsUnknownToolsByDefault(): void
    {
        $context = ['member_scopes' => ['b2b:read']];

        $unknownTool = new ToolCall('unknown_tool_name', [], 'result');

        self::assertTrue($this->guard->canExecuteTool($unknownTool, $context));
    }
}
