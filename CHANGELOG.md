# Changelog

All notable changes to `overtrue/laravel-text-guard` will be documented in this file.

## [1.0.3] - 2026-03-06

### Fixed
- Replaced recursive config merge with override-safe recursive replace to prevent scalar-to-array type breakages in runtime overrides.
- Hardened HTML whitelist sanitization using DOM traversal to remove unsafe attributes and disallowed URL schemes.
- Added pipeline factory guardrails to validate step class existence and interface compliance.
- Improved Unicode and regex failure handling to avoid `TypeError` on invalid UTF-8 inputs.
- Fixed zero-width character handling consistency by including `U+2060` across guards and sanitizers.
- Resolved PHP 8.4 trait static access deprecation risk by introducing centralized `TextGuardState`.
- Corrected Composer Laravel alias target for `TextGuard` facade class.

### Improved
- Added release script alias `composer fix` for consistent formatting workflow.
- Expanded unit tests for override-merging behavior, sanitization security, invalid input resilience, and pipeline factory validation.

[1.0.3]: https://github.com/overtrue/laravel-text-guard/compare/1.0.2...1.0.3
