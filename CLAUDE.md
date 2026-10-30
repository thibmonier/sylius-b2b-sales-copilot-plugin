# CLAUDE.md

Sylius 2 plugin "B2B Sales Copilot": conversational purchasing assistant for B2B customers. Buyers formulate requests in natural language, the copilot prepares the cart using MCP tools from `tmonier/sylius-b2b-mcp-plugin`.

**Demo mode (replay)**: pre-recorded responses from `config/cassettes.jsonl`, no LLM API calls.

Docs: `README.md`, ADR in `docs/adr/` (refer to the demo's `docs/adr/0018-copilote-format-rejeu-adaptateur.md` for the architecture decision).

## Commands

PHP needs more than 128 MB to compile the test application container: prefix console, PHPStan and functional PHPUnit calls with `php -d memory_limit=2G` when running locally.

```bash
vendor/bin/phpunit --testsuite=all
vendor/bin/phpstan analyse                # level max
vendor/bin/ecs check
vendor/bin/console lint:container
vendor/bin/console lint:twig templates
vendor/bin/console lint:yaml config translations
```

The test application reads variables from `phpunit.xml.dist` `<server>` tags (no `.env` files versioned).

## Architecture

- `Model/` (port: `CopilotModelInterface`, `CopilotResponse`, `ToolCall`)
- `Infrastructure/Replay/` (`ReplayCopilotAdapter`: reads cassettes from `config/cassettes.jsonl`, returns fallback for unknown requests)
- `Controller/` (widget endpoint, chat endpoint)
- `assets/` (Stimulus controller for the conversational widget)
- `templates/` (Twig template for the widget in the B2B account)

Rule: the copilot sees only what the signed-in member has permission to see (`b2b:read`, `b2b:cart` scopes).
