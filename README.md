# Sylius B2B Sales Copilot Plugin

English | [Français](README.fr.md)

Conversational purchasing assistant skeleton for Sylius 2 B2B: a widget in the customer account sends requests to a copilot model behind a port (`CopilotModelInterface`). This version ships the **replay adapter** only: answers come from pre-recorded cassettes, with no call to an LLM API.

**Demo**: [https://sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)  
**Author**: [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

## Features

- **Conversational widget** in the customer account dashboard (Stimulus, keyboard accessible, answers announced by a live region)
- **Replay mode**: recorded requests return their recorded answer; any other request returns « scénario non enregistré en démo »
- **Access guard**: the chat endpoint refuses anonymous visitors (401) and channels outside `enabled_channels` (403); the widget is only rendered on enabled channels
- **Functional guardrails**:
  - **Tool whitelist**: only approved tools can be called
  - **Scope-based authorization**: read tools require `b2b:read` scope, modification tools require `b2b:cart` scope
  - **Rate limiting**: maximum requests per member per day (configurable, default 50)
  - **Audit logging**: all tool executions are logged to a dedicated channel
  - **Automatic refusal**: checkout and payment operations are refused by design (security)

Natural-language actions on the store (reorder, quick add, quotes) through MCP tools are planned for a later version.

## Requirements

- PHP `^8.2`
- Sylius `^2.2.9 || ^2.3`
- Symfony `^7.4 || ^8.0`

## Installation

```bash
composer require tmonier/sylius-b2b-sales-copilot-plugin
```

Enable the bundle in `config/bundles.php`:

```php
return [
    // ...
    Tmonier\SyliusB2bSalesCopilotPlugin\TmonierSyliusB2bSalesCopilotPlugin::class => ['all' => true],
];
```

Import the routes in `config/routes/tmonier_sylius_b2b_sales_copilot.yaml`:

```yaml
tmonier_sylius_b2b_sales_copilot:
    resource: "@TmonierSyliusB2bSalesCopilotPlugin/config/routes.yaml"
```

Register the widget controller in your shop assets entrypoint:

```js
import '@vendor/tmonier/sylius-b2b-sales-copilot-plugin/assets/shop/entrypoint';
```

## Configuration

List the channels where the copilot is available (B2B channels):

```yaml
# config/packages/tmonier_sylius_b2b_sales_copilot.yaml
tmonier_sylius_b2b_sales_copilot:
    enabled_channels: ['B2B_WEB']
    
    # Optional: tool whitelist (default includes safe tools)
    tool_whitelist:
        - view_cart
        - get_cart
        - search_products
        - list_my_orders
        - add_to_cart
        - remove_from_cart
        - update_cart_quantity
        - reorder
    
    # Optional: rate limit per member per day (default: 50)
    daily_limit: 50
```

Recorded answers live in `config/cassettes.jsonl` (JSONL, one recorded request per line).

Audit logs are written to `var/log/copilot_audit.log`.

### LLM integration (optional)

The plugin can call a real LLM via [Symfony AI Platform](https://github.com/symfony/ai). When the LLM is unavailable, the plugin falls back to replay mode transparently.

```bash
composer require symfony/ai-platform symfony/ai-anthropic
```

```yaml
tmonier_sylius_b2b_sales_copilot:
    ai:
        enabled: true
        model: 'claude-sonnet-4-5'
        daily_cost_limit_cents: 500  # 0 = unlimited
```

Set your API key via environment variable as documented by Symfony AI.

When `ai.enabled` is `false` (default), only the replay adapter is used — no LLM dependency is needed.

### Example cassette

```jsonl
{"version":"1.0","intent":"état de mon panier","normalized":{"action":"get_cart"},"timestamp":"2026-10-31T10:00:00Z","tools":[{"name":"get_checkout_link","args":{},"result":"success"}],"response":"Votre panier contient 47 articles pour un total de 963,50 € HT. Voulez-vous passer au checkout ?"}
```

## Usage

A signed-in customer on an enabled channel sees the copilot widget on the account dashboard and can send a request. A recorded request (for example « état de mon panier ») returns its recorded answer.

The widget is hooked into `sylius_shop.account.dashboard.index.content.main.section`. A theme that replaces the dashboard can include it directly: `{% include '@TmonierSyliusB2bSalesCopilotPlugin/account/copilot_widget.html.twig' %}` (it renders nothing outside the enabled channels).

## Screenshots

![The widget on the account dashboard, after a recorded request](docs/images/shop-copilot-widget-en.jpg)

## Development

### Running tests

```bash
composer install
vendor/bin/phpunit --testsuite=all
```

### Code quality

```bash
vendor/bin/phpstan analyse
vendor/bin/ecs check
vendor/bin/rector process --dry-run
```

## License

MIT. See [LICENSE](LICENSE) file.

## Author

**Thibaut Monier**  
LinkedIn: [https://www.linkedin.com/in/thibautmonier/](https://www.linkedin.com/in/thibautmonier/)

## Contributing

Contributions are welcome. Please open an issue or pull request on GitHub.
