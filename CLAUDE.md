# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP_CodeSniffer (PHPCS) standard package (`drevops/phpcs-standard`) that enforces custom coding conventions, specifically focused on snake_case naming for local variables and parameters.

**Key Goals:**
- Enforce snake_case for local variables and function/method parameters
- Exclude class properties from snake_case enforcement (properties follow different conventions)
- Preserve inherited parameter names from interfaces and parent classes
- Provide auto-fixing support via `phpcbf`
- Provide a standalone, reusable PHPCS standard for the DrevOps ecosystem

## Development Commands

### Dependencies
```bash
composer install
```

### Linting
Runs PHPCS, PHPStan, and Rector in dry-run mode:
```bash
composer lint
```

Fix auto-fixable issues with PHPCBF and Rector:
```bash
composer lint-fix
```

### Testing
Run tests without coverage:
```bash
composer test
```

Run tests with coverage (generates HTML and Cobertura reports in `.logs/`):
```bash
composer test-coverage
```

Run a single test file:
```bash
./vendor/bin/phpunit tests/Unit/VariableSnakeCaseSniffTest.php
```

Run only unit tests or functional tests:
```bash
./vendor/bin/phpunit tests/Unit/
./vendor/bin/phpunit tests/Functional/
```

Run a specific test method:
```bash
./vendor/bin/phpunit --filter testMethodName
```

Update test fixtures (when tests fail due to expected output changes):
```bash
UPDATE_FIXTURES=1 ./vendor/bin/phpunit
```

### Other Commands
Reset dependencies:
```bash
composer reset
```

Validate composer.json:
```bash
composer validate
composer normalize --dry-run
```

## Code Architecture

### Directory Structure
- `src/DrevOps/` - Source code for the PHPCS standard
  - `Sniffs/NamingConventions/VariableSnakeCaseSniff.php` - Main sniff implementation
  - `ruleset.xml` - DrevOps standard definition
- `tests/` - PHPUnit tests organized by type:
  - `Unit/` - Unit tests for individual sniff methods (using reflection)
  - `Functional/` - Integration tests that run actual phpcs commands
  - `Fixtures/` - Test fixture files with intentional violations
- `.github/workflows/` - CI/CD pipelines
- `phpcs.xml` - Project's own PHPCS configuration (based on Drupal standard)

### Sniff Implementation

The `VariableSnakeCaseSniff` class (src/DrevOps/Sniffs/NamingConventions/VariableSnakeCaseSniff.php:16) is the core implementation:

**Key methods:**
- `register()` - Registers T_VARIABLE token for processing
- `process()` - Main processing logic that checks variable names
- `isReserved()` - Identifies PHP reserved variables ($this, $_GET, etc.)
- `isProperty()` - Distinguishes class properties from local variables
- `isInheritedParameter()` - Detects parameters from interfaces/parent classes
- `isSnakeCase()` - Validates snake_case format using regex
- `toSnakeCase()` - Converts camelCase to snake_case for suggestions

**What gets checked:**
- ✅ Local variables inside functions/methods
- ✅ Function and method parameters
- ✅ Closure parameters
- ❌ Class properties (public, private, protected, static)
- ❌ Promoted constructor properties
- ❌ Reserved PHP variables ($this, superglobals, etc.)
- ❌ Parameters inherited from interfaces/parent classes/abstract methods

**Error code:** `DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase`

### PHPCS Standard Registration

The standard is automatically registered via:
1. `composer.json` declares `type: "phpcodesniffer-standard"`
2. `extra.phpcodesniffer-standard` specifies the standard name: `"DrevOps"`
3. `dealerdirect/phpcodesniffer-composer-installer` plugin handles registration
4. Standard definition in `src/DrevOps/ruleset.xml` references the sniff

Verify installation with: `vendor/bin/phpcs -i` (should list "DrevOps")

### Testing Strategy

This project uses **two complementary testing approaches** (see TESTING.md for details):

1. **Unit Tests** (`tests/Unit/VariableSnakeCaseSniffTest.php`)
   - Test individual private methods using PHP reflection
   - Fast execution, easy debugging
   - Cover: `isSnakeCase()`, `toSnakeCase()`, `isReserved()`, `register()`
   - 28 tests covering internal logic

2. **Functional Tests** (`tests/Functional/VariableSnakeCaseSniffFunctionalTest.php`)
   - Run actual `phpcs` commands as external processes
   - Test complete PHPCS integration with JSON output parsing
   - Verify error codes, messages, and detection accuracy
   - 3 tests covering real-world usage:
     - `testSniffDetectsViolations()` - Confirms violations are found
     - `testSniffIgnoresProperties()` - Confirms properties are ignored
     - `testCleanFilePassesValidation()` - Confirms valid code passes

**Test fixtures:**
- `tests/Fixtures/VariableNaming.php` - Contains intentional violations
- `tests/Fixtures/InheritedParameters.php` - Tests interface/parent class scenarios
- `tests/Fixtures/Valid.php` - Clean code for positive testing
- Fixtures are excluded from linting in `phpcs.xml` and `rector.php`

### Code Quality Tools

1. **PHPCS** - Code style checking (Drupal standard + strict types)
   - Project's `phpcs.xml` uses Drupal base + `Generic.PHP.RequireStrictTypes`
   - Relaxes array line length and function comment rules for test files
2. **PHPStan** (Level 9) - Static analysis with strict type checking
3. **Rector** - Automated refactoring and code modernization targeting PHP 8.3+
4. **PHPUnit 11** - Testing framework with coverage reporting

### Key Technical Details

- **PHP Version:** Requires PHP 8.3+ (composer.json)
- **Namespace:** `DrevOps\` for sniff classes, `DrevOps\PhpcsStandard\Tests\` for tests
- **Autoloading:** PSR-4 autoloading for both source and tests
- **Strict Types:** All PHP files must declare `strict_types=1`
- **Standard Name:** "DrevOps" (registered via composer plugin)
- **Test Coverage:** Reports generated in `.logs/.coverage-html/` and `.logs/cobertura.xml`

## PHPCS Sniff Development Guidelines

When implementing or modifying sniffs:

1. Place sniff classes in `src/DrevOps/Sniffs/` following PHPCS naming conventions
   - Format: `CategoryName/SniffNameSniff.php`
   - Example: `NamingConventions/VariableSnakeCaseSniff.php`
2. Implement the `Sniff` interface from `PHP_CodeSniffer\Sniffs\Sniff`
3. Use `declare(strict_types=1);` at the top of all PHP files
4. Register tokens in `register()` method (return array of T_* constants)
5. Process tokens in `process(File $phpcsFile, $stackPtr)` method
6. Use `addFixableError()` for violations that can be auto-fixed with phpcbf
7. Create both unit tests and functional tests:
   - Unit tests for internal method logic
   - Functional tests for complete PHPCS integration
8. Create fixture files in `tests/Fixtures/` with intentional violations
9. Follow error code naming: `StandardName.Category.SniffName.ErrorName`
   - Example: `DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase`

## CI/CD

GitHub Actions workflow (`test-php.yml`) runs on:
- Push to `main` branch
- Pull requests to `main` or `feature/**` branches
- Manual workflow dispatch (with optional terminal session for debugging)

Tests run across PHP versions: 8.3, 8.4, 8.5 (with `--ignore-platform-reqs` for 8.5)

Workflow steps:
1. Composer validation and normalization check
2. Code standards check (`composer lint`) - can continue on error via `CI_LINT_IGNORE_FAILURE` variable
3. Tests with coverage (`composer test-coverage`)
4. Upload coverage artifacts
5. Upload results to Codecov (test results + coverage reports)

## Code Style Conventions

- Use snake_case for local variables and method parameters
- Use camelCase for method names and class properties
- Strict types declaration required in all files: `declare(strict_types=1);`
- Follow Drupal coding standards with added strict type requirements
- Single quotes for strings (double quotes when containing single quotes)
- Files must end with a newline character
