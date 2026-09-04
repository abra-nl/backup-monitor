# Code Quality Tools

This package uses the same tooling as Abra's other Statamic addons:

- **Laravel Pint**: code formatting and style fixing
- **PHPStan**: static analysis for type safety and bug detection
- **Rector**: automated refactoring and code modernization

## Tools Overview

### Laravel Pint

- Configuration: `pint.json`
- Laravel preset with custom tweaks for package development.

### PHPStan

- Configuration: `phpstan.neon`
- Level 6, `src/` only (tests are excluded — Testbench/Statamic types make deep analysis of test files noisy without much payoff).

### Rector

- Configuration: `rector.php`
- PHP 8.1-targeted rule sets (code quality, coding style, dead code, early return, type declarations, privatization), run with `--dry-run` before ever applying.

## Available Commands

```bash
# Laravel Pint
composer pint                # Fix formatting issues
composer pint:check          # Check formatting without fixing
composer pint:dirty          # Format only changed files (git)

# PHPStan
composer stan                # Run static analysis
composer stan:baseline       # Generate a baseline for existing issues

# Rector
composer rector              # Apply refactoring changes
composer rector:dry          # Preview changes without applying

# Combined
composer code:check          # pint:check + stan + test
composer code:fix            # pint + rector + test
```

## Recommended Workflow

Before committing:

```bash
composer code:fix
git diff   # review what Rector changed
```

In CI:

```bash
composer code:check
```

## Handling Issues

- **PHPStan false positives on Collection chains**: this codebase leans on Spatie's `BackupCollection`/`Collection::map()` chains. Plain `phpstan/phpstan` (no Larastan) can't always infer the callback's return shape through Collection's generics — where that happens, prefer a narrowly-scoped `// @phpstan-ignore-next-line` with a one-line reason over broadening `phpstan.neon`'s global `ignoreErrors`.
- **Legitimate issues that can't be fixed immediately**: `composer stan:baseline` to snapshot them without blocking new code.
- **Rector changes**: always review with `composer rector:dry` before running `composer rector`.
