# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0 - 2026-11-17

First release: CCPA/CPRA minors (Cal. Civ. Code §1798.120(c)) for Laravel, part of the laravel-ccpa suite.

- Age check results only: age, method and provider. Reliable checks outweigh self-declaration, and the youngest reliable result wins.
- No sale or sharing of the personal information of a consumer known to be under 16 without opt-in: from the consumer at 13 to 15, from a verified parent or guardian under 13. Either can revoke it.
- `allowsSaleOrSharing()`, the `@ccpaMinorSaleAllowed` Blade condition and `ccpa:minors [--json]`.
- `treat_unknown_as_minor` for services directed to children, and the `ccpa.adult` middleware.

## [Unreleased]
