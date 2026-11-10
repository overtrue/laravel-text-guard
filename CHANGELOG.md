# Changelog

All notable changes to `overtrue/laravel-text-guard` will be documented in this file.

## Unreleased — composable pipeline redesign (breaking)

- Execute steps in preset order; add direct TextGuard::pipe() composition without a preset. Finish built-in normalization with trimming so explicit cleanup produces values accepted by unchanged-input validation.
- Pass step options transparently, including empty arrays and keys named value; reject unknown names and invalid top-level option types.
- Replace list overrides atomically, including HTML tag, attribute and protocol allowlists.
- Make Filtered a pure unchanged-input validation rule, with explicit cleanup before validation; validate() now uses the complete pipeline.
- Add Request::mergeFiltered() for field => preset maps (wildcard patterns on dotted keys), Sanitize validation rule that writes into validator data only, and opt-in text-guard.merge route middleware alias (never global).
- Reject invalid UTF-8 at the executor boundary and stop silently falling back on misspelled model presets.
- Add independent bidi-control removal, optional ZWNJ/ZWJ preservation and grapheme truncation.
- Replace legacy emoji block options with whole-sequence Unicode matching; update nickname policy and reject obsolete emoji_ranges configuration.
- Require ext-dom for HTML whitelist filtering rather than degrading to tag-only filtering.
- Consolidate English/Chinese documentation with composition boundaries and migration instructions. No application-specific masking rules are introduced.

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
