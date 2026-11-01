<?php

declare(strict_types=1);

namespace Tmonier\SyliusB2bSalesCopilotPlugin\Guard;

use Tmonier\SyliusB2bSalesCopilotPlugin\Model\ToolCall;

final readonly class CopilotScopeGuard
{
    private const READ_TOOLS = [
        'view_cart',
        'search_products',
        'list_my_orders',
        'get_cart',
    ];

    private const MODIFICATION_TOOLS = [
        'add_to_cart',
        'remove_from_cart',
        'update_cart_quantity',
        'reorder',
    ];

    /**
     * @param array<string, mixed> $context
     */
    public function canExecuteTool(ToolCall $tool, array $context): bool
    {
        $scopes = $context['member_scopes'] ?? [];

        if (!is_array($scopes)) {
            return false;
        }

        if (in_array($tool->name, self::READ_TOOLS, true)) {
            return in_array('b2b:read', $scopes, true);
        }

        if (in_array($tool->name, self::MODIFICATION_TOOLS, true)) {
            return in_array('b2b:cart', $scopes, true);
        }

        return true;
    }
}
