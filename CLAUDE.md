# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP_CodeSniffer (PHPCS) standard package (`drevops/phpcs-standard`) that enforces custom coding conventions, specifically focused on configurable naming (snakeCase or camelCase) for local variables and parameters.

**Key Goals:**
- Enforce consistent naming conventions for local variables and function/method parameters
- Support configurable formats: snakeCase (default) or camelCase
- Exclude class properties from naming enforcement (properties follow different conventions)
- Preserve parameter names that an ancestor class, interface or trait declares for the same method
- Resolve ancestors in Drupal extension namespaces, which Drupal registers at runtime rather than through Composer
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
./vendor/bin/phpunit tests/Unit/AbstractVariableNamingSniffTest.php
./vendor/bin/phpunit tests/Unit/LocalVariableNamingSniffTest.php
./vendor/bin/phpunit tests/Unit/ParameterNamingSniffTest.php
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
  - `Helpers/` - Non-sniff classes (outside `Sniffs/`, so PHPCS does not register them)
    - `ClassLikeDeclaration.php` - Value object: a class-like's name, ancestors and non-private method parameter names
    - `ClassLikeParser.php` - Reads class-like declarations, namespaces and imports from a token stream
    - `DrupalNamespaceMap.php` - Maps Drupal extension namespaces to their `src` and `tests/src` directories
    - `DrupalRootResolver.php` - Resolves the Drupal root from the `drupalRoot` property or the `drupal/core` install path
    - `InheritanceResolver.php` - Finds the parameter names that ancestors declare for a method
  - `Sniffs/NamingConventions/`
    - `AbstractVariableNamingSniff.php` - Base class with shared functionality
    - `LocalVariableNamingSniff.php` - Enforces snake_case for local variables
    - `ParameterNamingSniff.php` - Enforces snake_case for parameters
  - `ruleset.xml` - DrevOps standard definition
- `tests/` - PHPUnit tests organized by type:
  - `Unit/` - Unit tests for individual sniff methods (using reflection)
    - `AbstractVariableNamingSniffTest.php` - Tests shared base class methods
    - `LocalVariableNamingSniffTest.php` - Tests local variable sniff
    - `ParameterNamingSniffTest.php` - Tests parameter sniff
    - `ClassLikeParserTest.php` - Tests declaration parsing and class name resolution
    - `DrupalNamespaceMapTest.php` - Tests extension discovery and class file lookup
    - `DrupalRootResolverTest.php` - Tests root detection and the `drupalRoot` values
    - `InheritanceResolverTest.php` - Tests ancestor lookup and inherited parameter names
    - `UnitTestCase.php` - Base test class with helper methods
  - `Functional/` - Integration tests that run actual phpcs commands
  - `Fixtures/` - Test fixture files with intentional violations
- `.github/workflows/` - CI/CD pipelines
- `phpcs.xml` - Project's own PHPCS configuration (based on Drupal standard)

### Sniff Implementation

The standard uses an **abstract base class pattern** with two concrete implementations:

#### AbstractVariableNamingSniff

Base class (src/DrevOps/Sniffs/NamingConventions/AbstractVariableNamingSniff.php) containing shared functionality:

**Public properties:**
- `$format` - Configurable naming convention ('snakeCase' or 'camelCase', default: 'snakeCase')
- `$drupalRoot` - Drupal root for resolving ancestors in Drupal extension namespaces (unset: detected from `drupal/core`; a path: replaces the detected root, relative to the working directory; `FALSE`: off). Only ParameterNaming looks ancestors up, so LocalVariableNaming ignores it

**Core methods:**
- `register()` - Registers T_VARIABLE token for processing
- `isReserved()` - Identifies PHP reserved variables ($this, $_GET, etc.)
- `isSnakeCase()` - Validates snake_case format using regex
- `isCamelCase()` - Validates camelCase format using regex
- `isValidFormat()` - Validates variable name against configured format
- `toSnakeCase()` - Converts variable name to snake_case
- `toCamelCase()` - Converts variable name to camelCase
- `toFormat()` - Converts variable name to the configured format

**Helper methods:**
- `getParameterNames()` - Extracts parameter names from a function, closure or arrow function signature, or from a closure `use` clause (via `File::getMethodParameters()`; a `use` clause without parentheses yields no names instead of an exception)
- `findParameterListOwner()` - Finds the function, closure or arrow function whose parameter list contains a variable (from `nested_parenthesis`; a closure body nested in the list, as a PHP 8.5 default value or attribute argument, does not count)
- `findEnclosingFunction()` - Finds the innermost function, closure or arrow function whose body contains a token (innermost `conditions` entry, stopping at class-likes, then a backwards search for `T_FN`, which PHPCS never adds to `conditions`)
- `findDeclaringFunction()` - Finds the function that declares a variable as a parameter, moving outwards only through closures that import it with `use` and through arrow functions
- `capturesVariable()` - Checks if a closure (`use` clause) or an arrow function (always) takes a variable from the enclosing scope
- `isPromotedProperty()` - Detects promoted constructor properties
- `isParameter()` - Checks if variable is a parameter (signature only, or with uses in bodies resolved through `findDeclaringFunction()`)
- `isProperty()` - Distinguishes class properties from local variables
- `isInheritedParameter()` - Detects parameters whose name an ancestor declares for the same method (delegates to `InheritanceResolver`, created once with the root that `DrupalRootResolver` resolves from `$drupalRoot`)

#### InheritanceResolver

Resolves the ancestors of the method's class-like (extended class, implemented or extended interfaces, used traits, transitively) and returns the parameter names they declare for the method:
- Lookup order: the file being checked, classes already loaded in the PHPCS process (`ReflectionClass`, autoloading disabled), source files located by Composer's registered class loaders (`findFile()`), then, when constructed with a Drupal root, source files located by `DrupalNamespaceMap`. Located files are tokenized with the PHPCS tokenizer; project code is never included or executed.
- Returns `[]` when no ancestor declares the method, the declared names when one does, and `NULL` when no resolved ancestor declares it and an ancestor is unresolved (the sniff then skips every parameter of the method).
- Private methods, closures, arrow functions and global functions are never inherited.
- Caches lookups per class name, parsed source files per path, and the current file's declarations per token stream (path, object id, fixer loop, token count).
- Names are built from token content, so PHPCS 3 (`T_STRING` + `T_NS_SEPARATOR`) and PHPCS 4 (`T_NAME_*`) give the same result.

#### DrupalRootResolver

Turns the `drupalRoot` property into an absolute Drupal root or `NULL`:
- `FALSE` turns discovery off. A non-blank string is a path, resolved with `realpath()` from the working directory; a path without `core/lib/Drupal.php` throws a `RuntimeException`, which PHPCS reports as `Internal.Exception` on the file (as for an invalid `format`).
- Any other value (`NULL`, empty, `TRUE`) detects the root as the parent of the `drupal/core` install path from `Composer\InstalledVersions`, kept only when it holds `core/lib/Drupal.php`.
- The `drupal/core` lookup is an injectable closure, so tests can point it at `tests/Fixtures/Drupal/core`.

#### DrupalNamespaceMap

Maps `Drupal\<extension>\` to `<extension>/src` and `Drupal\Tests\<extension>\` to `<extension>/tests/src`:
- Scans `core/{modules,profiles,themes}`, `{modules,profiles,themes}` and `sites/*/{modules,profiles,themes}` recursively for `*.info.yml`, the same directories as Drupal's PHPUnit bootstrap. A later directory wins for a duplicate machine name (core < root < sites).
- Skips hidden directories and the directories Drupal's extension discovery never enters (`src`, `vendor`, `node_modules`, `fixtures`, ...); scans `tests` for test modules and `config` for core's `config` module.
- Follows symlinks, records real paths, and stops at directories already visited, so symlink cycles end.
- Scans lazily on the first `Drupal\<extension>\<class>` lookup, once per instance; other namespaces return `NULL` without scanning.
- Namespaces outside extensions (core's `Drupal\KernelTests\`, `Drupal\Tests\` in `core/tests`) are left to Composer.

#### LocalVariableNamingSniff

Enforces configurable naming convention for **local variables** inside functions/methods.

**What gets checked:**
- ✅ Local variables inside function/method bodies, including variables a closure uses without importing them
- ❌ Parameters of functions, methods, closures and arrow functions, including uses imported with `use` or captured by arrow functions (handled by ParameterNaming)
- ❌ Class properties (not enforced)
- ❌ Reserved PHP variables ($this, superglobals, etc.)

**Error codes:**
- `DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase` (when format='snakeCase')
- `DrevOps.NamingConventions.LocalVariableNaming.NotCamelCase` (when format='camelCase')

#### ParameterNamingSniff

Enforces configurable naming convention for **function/method parameters**.

**What gets checked:**
- ✅ Parameters of functions, methods, closures, arrow functions and anonymous class methods (in signature only)
- ❌ Local variables (handled by LocalVariableNaming)
- ❌ Parameters whose name an ancestor class, interface or trait declares for the same method (renamed and extra parameters are checked; interface and abstract declarations are checked unless they redeclare an ancestor method)
- ❌ Promoted constructor properties

**Error codes:**
- `DrevOps.NamingConventions.ParameterNaming.NotSnakeCase` (when format='snakeCase')
- `DrevOps.NamingConventions.ParameterNaming.NotCamelCase` (when format='camelCase')

### PHPCS Standard Registration

The standard is automatically registered via:
1. `composer.json` declares `type: "phpcodesniffer-standard"`
2. `extra.phpcodesniffer-standard` specifies the standard name: `"DrevOps"`
3. `dealerdirect/phpcodesniffer-composer-installer` plugin handles registration
4. Standard definition in `src/DrevOps/ruleset.xml` references the sniff

Verify installation with: `vendor/bin/phpcs -i` (should list "DrevOps")

### Testing Strategy

This project uses **two complementary testing approaches**:

#### 1. Unit Tests (549 tests, 616 assertions, 100% coverage)

Tests are organized by class hierarchy:

**AbstractVariableNamingSniffTest.php**
- Tests all shared base class methods using reflection
- Tests: `isSnakeCase()`, `toSnakeCase()`, `isReserved()`, `register()`, `getParameterNames()`, `findParameterListOwner()`, `findEnclosingFunction()`, `findDeclaringFunction()`, `capturesVariable()`, `isParameter()`, `isProperty()`, `isPromotedProperty()`, `isInheritedParameter()`
- Each test uses concrete sniff instances (LocalVariableNamingSniff or ParameterNamingSniff) to access protected methods

**LocalVariableNamingSniffTest.php**
- Tests sniff-specific logic: error code constant and `process()` method
- Configured to run only LocalVariableNaming sniff in isolation
- Validates that local variables are checked and parameters are skipped

**ParameterNamingSniffTest.php**
- Tests sniff-specific logic: error code constant, `register()`, and `process()` method
- Configured to run only ParameterNaming sniff in isolation
- Validates that parameters are checked and local variables are skipped
- Includes tests for inherited parameter detection, with `drupalRoot` set on the ruleset's sniff instance

**ClassLikeParserTest.php** and **InheritanceResolverTest.php**
- Cover namespaces, imports, name resolution, every class-like kind, same-file, reflected, Composer-located and Drupal-located ancestors, unresolved ancestors, cycles and caching
- `InheritanceResolver` accepts a source locator closure, so tests can point class names at fixture files, and a Drupal root, so tests can point it at `tests/Fixtures/Drupal`

**DrupalNamespaceMapTest.php** and **DrupalRootResolverTest.php**
- `DrupalNamespaceMapTest` builds a Drupal root per data set in a `LocationsTrait` workspace, which also covers symlinked extensions, symlink cycles, unreadable directories (skipped when permissions are not enforced) and the single lazy scan
- `DrupalRootResolverTest` switches the working directory to the project root, so relative `drupalRoot` paths resolve the same way everywhere

**Key testing patterns:**
- Use PHP reflection to test protected methods
- Use `processCode()` helper to simulate PHPCS token processing
- Use `findVariableToken()` (with an optional occurrence), `findTokenByCode()` (with an optional occurrence), `findFunctionToken()` and `findFunctionTokenByName()` helpers to locate tokens
- Lookups that return a token position are asserted with `assertFunctionLookup()`, which takes the expected token as a `[code, occurrence]` pair
- Each concrete sniff test overrides `setUp()` to configure specific sniff isolation

#### 2. Functional Tests

**LocalVariableNamingSniffFunctionalTest.php**
- Run actual `phpcs` commands as external processes
- Test complete PHPCS integration with JSON output parsing
- Verify LocalVariableNaming sniff detection and error codes

**ParameterNamingSniffFunctionalTest.php**
- Run actual `phpcs` commands as external processes
- Test complete PHPCS integration with JSON output parsing
- Verify ParameterNaming sniff detection and error codes
- Drupal discovery tests write a ruleset that sets `drupalRoot` on the standard and pass its path to `runPhpcs()`; `runPhpcsJson()` returns the violations for assertions that cannot match whole messages

Tests include:
- Confirms violations are detected with correct error codes
- Confirms correct exclusions (properties, inherited parameters, etc.)
- Validates clean code passes without errors

**Test fixtures:**
- `tests/Fixtures/VariableNaming.php` - Contains intentional violations
- `tests/Fixtures/InheritedParameters.php` - Same-file ancestors: interfaces, abstract classes, renamed and extra parameters
- `tests/Fixtures/InheritedParametersCrossFile.php` - Ancestors in other files, a PHPCS interface, internal classes, an enum, an anonymous class and an unresolved parent
- `tests/Fixtures/Inheritance/` - One ancestor per file, autoloaded through the `autoload-dev` PSR-4 mapping so Composer can locate them
- `tests/Fixtures/Drupal/` - A minimal Drupal root: a core module class and test class, a contrib interface, a module duplicated in `modules/contrib` and `sites/default/modules`, and the checked `my_module` file. Excluded from the `autoload-dev` classmap (`exclude-from-classmap`), so only `DrupalNamespaceMap` locates its classes, and from PHPStan
- `tests/Fixtures/NestedFunctionParameters.php` - Closures, arrow functions and anonymous class methods: parameters, `use` imports, arrow function captures, shadowing and locals of nested functions
- `tests/Fixtures/Valid.php` - Clean code for positive testing
- Fixtures are excluded from linting in `phpcs.xml` and `rector.php`
- No fixture file name ends in `Test.php`, as PHPUnit would load it as a test
- `runPhpcbf()` fixes a copy that keeps the `.php` extension, because PHPCS 3 skips files without a known extension

**Coverage:**
- Line coverage: 100% (713/713 lines covered)
- Reports: `.logs/.coverage-html/index.html` and `.logs/cobertura.xml`

### Code Quality Tools

1. **PHPCS** - Code style checking (Drupal standard + strict types)
   - Project's `phpcs.xml` uses Drupal base + `Generic.PHP.RequireStrictTypes`
   - Relaxes array line length and function comment rules for test files
2. **PHPStan** (Level 9) - Static analysis with strict type checking
3. **Rector** - Automated refactoring and code modernization targeting PHP 8.3+
4. **PHPUnit 12** - Testing framework with coverage reporting

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
   - Example: `NamingConventions/LocalVariableNamingSniff.php`
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
      - `DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase`
      - `DrevOps.NamingConventions.LocalVariableNaming.NotCamelCase`
      - `DrevOps.NamingConventions.ParameterNaming.NotSnakeCase`
      - `DrevOps.NamingConventions.ParameterNaming.NotCamelCase`

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
