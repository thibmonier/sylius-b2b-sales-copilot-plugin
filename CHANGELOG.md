# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

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

[Unreleased]: https://github.com/thibmonier/sylius-b2b-sales-copilot-plugin/commits/main
