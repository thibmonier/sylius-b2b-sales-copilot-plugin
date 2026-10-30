# Sylius B2B Sales Copilot Plugin

English | [Français](README.fr.md)

AI-powered B2B purchasing assistant for Sylius 2: buyers formulate their purchase requests in natural language from their account, and the copilot prepares the cart from past orders and quotes.

**Demo**: [https://sylius-b2b.thibaut-monier.fr](https://sylius-b2b.thibaut-monier.fr)  
**Author**: [Thibaut Monier](https://www.linkedin.com/in/thibautmonier/)

## Features

- **Conversational widget** in the B2B customer account
- **Natural language** purchase requests (reorder, quick add by SKU, quote request)
- **Replay mode** for public demo (pre-recorded responses, no LLM API calls)
- **Audit trail** of all copilot actions
- **Scope-based permissions** (`b2b:read`, `b2b:cart`)

Actions on the store through MCP tools are planned for a later version.

## Requirements

- PHP `^8.2`
- Sylius `^2.2.9 || ^2.3`
- Symfony `^6.4 || ^7.4 || ^8.0`

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

Run migrations:

```bash
php bin/console doctrine:migrations:migrate
```

## Configuration

The plugin runs in **replay mode** by default (pre-recorded responses for demo purposes).

Cassette file: `config/cassettes.jsonl` (JSONL format, one response per line).

### Example cassette

```jsonl
{"version":"1.0","intent":"état de mon panier","normalized":{"action":"get_cart"},"timestamp":"2026-10-30T10:00:00Z","tools":[{"name":"get_checkout_link","args":{},"result":"success"}],"response":"Votre panier contient 3 articles pour un total de 150,00 €. Voulez-vous passer au checkout ?"}
```

## Usage

Once installed, B2B customers see a copilot widget in their account dashboard. They can:

- Ask for cart status
- Reorder from past orders
- Add products by SKU
- Request quotes

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
