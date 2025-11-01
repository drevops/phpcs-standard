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

Coverage reports are generated in:
- HTML: `.logs/.coverage-html/index.html`
- Cobertura XML: `.logs/cobertura.xml`

Run a single test file:
```bash
./vendor/bin/phpunit tests/Unit/AbstractVariableSnakeCaseSniffTest.php
./vendor/bin/phpunit tests/Unit/LocalVariableSnakeCaseSniffTest.php
./vendor/bin/phpunit tests/Unit/ParameterSnakeCaseSniffTest.php
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
  - `Sniffs/NamingConventions/`
    - `AbstractSnakeCaseSniff.php` - Base class with shared functionality
    - `LocalVariableSnakeCaseSniff.php` - Enforces snake_case for local variables
    - `ParameterSnakeCaseSniff.php` - Enforces snake_case for parameters
  - `ruleset.xml` - DrevOps standard definition
- `tests/` - PHPUnit tests organized by type:
  - `Unit/` - Unit tests for individual sniff methods (using reflection)
    - `AbstractVariableSnakeCaseSniffTest.php` - Tests shared base class methods
    - `LocalVariableSnakeCaseSniffTest.php` - Tests local variable sniff
    - `ParameterSnakeCaseSniffTest.php` - Tests parameter sniff
    - `UnitTestCase.php` - Base test class with helper methods
  - `Functional/` - Integration tests that run actual phpcs commands
  - `Fixtures/` - Test fixture files with intentional violations
- `.github/workflows/` - CI/CD pipelines
- `phpcs.xml` - Project's own PHPCS configuration (based on Drupal standard)

### Sniff Implementation

The standard uses an **abstract base class pattern** with two concrete implementations:

#### AbstractSnakeCaseSniff

Base class (src/DrevOps/Sniffs/NamingConventions/AbstractSnakeCaseSniff.php) containing shared functionality:

**Core methods:**
- `register()` - Registers T_VARIABLE token for processing
- `isReserved()` - Identifies PHP reserved variables ($this, $_GET, etc.)
- `isSnakeCase()` - Validates snake_case format using regex
- `toSnakeCase()` - Converts camelCase to snake_case for suggestions

**Helper methods:**
- `getParameterNames()` - Extracts parameter names from function signature
- `isInParameterList()` - Checks if variable is in parameter list
- `findEnclosingFunction()` - Finds the enclosing function/closure for a variable
- `isPromotedProperty()` - Detects promoted constructor properties
- `isParameter()` - Checks if variable is a function/method parameter (with flag for body usage)
- `isProperty()` - Distinguishes class properties from local variables
- `isInheritedParameter()` - Detects parameters from interfaces/parent classes

#### LocalVariableSnakeCaseSniff

Enforces snake_case for **local variables** inside functions/methods.

**What gets checked:**
- ✅ Local variables inside function/method bodies
- ❌ Function/method parameters (handled by ParameterSnakeCase)
- ❌ Class properties (not enforced)
- ❌ Reserved PHP variables ($this, superglobals, etc.)

**Error code:** `DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase`

#### ParameterSnakeCaseSniff

Enforces snake_case for **function/method parameters**.

**What gets checked:**
- ✅ Function and method parameters (in signature only)
- ❌ Local variables (handled by LocalVariableSnakeCase)
- ❌ Parameters inherited from interfaces/parent classes/abstract methods
- ❌ Promoted constructor properties

**Error code:** `DrevOps.NamingConventions.ParameterSnakeCase.NotSnakeCase`

### PHPCS Standard Registration

The standard is automatically registered via:
1. `composer.json` declares `type: "phpcodesniffer-standard"`
2. `extra.phpcodesniffer-standard` specifies the standard name: `"DrevOps"`
3. `dealerdirect/phpcodesniffer-composer-installer` plugin handles registration
4. Standard definition in `src/DrevOps/ruleset.xml` references the sniff

Verify installation with: `vendor/bin/phpcs -i` (should list "DrevOps")

### Testing Strategy

This project uses **two complementary testing approaches**:

#### 1. Unit Tests (88 tests, 135 assertions, 100% coverage)

Tests are organized by class hierarchy:

**AbstractVariableSnakeCaseSniffTest.php**
- Tests all shared base class methods using reflection
- Tests: `isSnakeCase()`, `toSnakeCase()`, `isReserved()`, `register()`, `getParameterNames()`, `isProperty()`, `isPromotedProperty()`, `isInheritedParameter()`
- Each test uses concrete sniff instances (LocalVariableSnakeCaseSniff or ParameterSnakeCaseSniff) to access protected methods

**LocalVariableSnakeCaseSniffTest.php**
- Tests sniff-specific logic: error code constant and `process()` method
- Configured to run only LocalVariableSnakeCase sniff in isolation
- Validates that local variables are checked and parameters are skipped

**ParameterSnakeCaseSniffTest.php**
- Tests sniff-specific logic: error code constant, `register()`, and `process()` method
- Configured to run only ParameterSnakeCase sniff in isolation
- Validates that parameters are checked and local variables are skipped
- Includes tests for inherited parameter detection

**Key testing patterns:**
- Use PHP reflection to test protected methods
- Use `processCode()` helper to simulate PHPCS token processing
- Use `findVariableToken()` and `findFunctionToken()` helpers to locate tokens
- Each concrete sniff test overrides `setUp()` to configure specific sniff isolation

#### 2. Functional Tests

**LocalVariableSnakeCaseSniffFunctionalTest.php**
- Run actual `phpcs` commands as external processes
- Test complete PHPCS integration with JSON output parsing
- Verify LocalVariableSnakeCase sniff detection and error codes

**ParameterSnakeCaseSniffFunctionalTest.php**
- Run actual `phpcs` commands as external processes
- Test complete PHPCS integration with JSON output parsing
- Verify ParameterSnakeCase sniff detection and error codes

Tests include:
- Confirms violations are detected with correct error codes
- Confirms correct exclusions (properties, inherited parameters, etc.)
- Validates clean code passes without errors

**Test fixtures:**
- `tests/Fixtures/VariableNaming.php` - Contains intentional violations
- `tests/Fixtures/InheritedParameters.php` - Tests interface/parent class scenarios
- `tests/Fixtures/Valid.php` - Clean code for positive testing
- Fixtures are excluded from linting in `phpcs.xml` and `rector.php`

**Coverage:**
- Line coverage: 100% (158/158 lines covered)
- Reports: `.logs/.coverage-html/index.html` and `.logs/cobertura.xml`

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
   - Example: `NamingConventions/LocalVariableSnakeCaseSniff.php`
2. Consider using abstract base classes for shared functionality across related sniffs
3. Implement the `Sniff` interface from `PHP_CodeSniffer\Sniffs\Sniff`
4. Use `declare(strict_types=1);` at the top of all PHP files
5. Register tokens in `register()` method (return array of T_* constants)
6. Process tokens in `process(File $phpcsFile, $stackPtr)` method
7. Use `addFixableError()` for violations that can be auto-fixed with phpcbf
8. Mark auto-fix code blocks with `@codeCoverageIgnore` (not testable in unit tests)
9. Create both unit tests and functional tests:
   - Unit tests for internal method logic using reflection
   - Functional tests for complete PHPCS integration
   - Organize tests by class hierarchy (abstract base tests separate from concrete tests)
10. Create fixture files in `tests/Fixtures/` with intentional violations
11. Follow error code naming: `StandardName.Category.SniffName.ErrorName`
    - Examples:
      - `DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase`
      - `DrevOps.NamingConventions.ParameterSnakeCase.NotSnakeCase`

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
