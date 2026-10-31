# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

## [1.2.0] - 2026-10-31

### Added
- Add-by-reference cassettes: `quick_order_add` single and batch (FR/EN) (US-213)
- Enriched cart response with Top 3 items and markdown formatting (US-213)
- Error cassettes: unknown reference, closed-catalog product, invalid quantity (FR/EN) (US-213)
- Alias matching: cassettes now support an `aliases` field for synonym matching (US-215)
- Keyword matching: fuzzy fallback when exact intent and alias both miss (US-215)
- Improved fallback message: lists available scenarios in the detected language (US-215)
- Suggestion chips for add-by-reference and batch-add in widget translations (US-213)
- Add-by-reference chip added to the widget template (US-213)

### Fixed
- ECS style violations in `ReplayCopilotAdapter` (constant prefix, closure spacing)
- PHPStan type assertions in functional tests (`assertIsString`, `assertIsArray`)
- Unused import in `WidgetOnDashboardTest`

## [1.1.0] - 2026-10-31

### Added
- Reorder scenarios: "reorder like last month" and "reorder with substitution" cassettes (FR/EN)
- Scenario suggestion buttons (chips) in the widget for quick access to recorded responses
- BEM class naming for the widget template (`.copilot-widget__*`)
- Empty state message when the conversation starts
- Fallback and error UI messages (title + body)

### Changed
- Widget template rewritten with BEM classes and `aria-live` region

## [0.1.1] - 2026-10-31

### Fixed
- The widget posts to the chat route of the current locale: in 0.1.0 it posted to `/api/copilot/chat`, while the route is prefixed with `/{_locale}`, so every request from the widget answered 404

## [0.1.0] - 2026-10-30

### Added
- Initial plugin skeleton
- Replay adapter for demo mode (pre-recorded responses)
- Conversational widget in B2B customer account
- Installation tests
- Access guard on the chat endpoint (anonymous visitors refused, `enabled_channels` option)
- Fallback answer for requests absent from the cassettes

[Unreleased]: https://github.com/thibmonier/sylius-b2b-sales-copilot-plugin/compare/v1.2.0...main
[1.2.0]: https://github.com/thibmonier/sylius-b2b-sales-copilot-plugin/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/thibmonier/sylius-b2b-sales-copilot-plugin/compare/v0.1.1...v1.1.0
