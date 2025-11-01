<p align="center">
  <a href="" rel="noopener">
  <img width=200px height=200px src="logo.png" alt="DrevOps PHP_CodeSniffer Standard logo"></a>
</p>

<h1 align="center">DrevOps PHP_CodeSniffer Standard</h1>

<div align="center">

[![GitHub Issues](https://img.shields.io/github/issues/drevops/phpcs-standard.svg)](https://github.com/drevops/phpcs-standard/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/drevops/phpcs-standard.svg)](https://github.com/drevops/phpcs-standard/pulls)
[![Test PHP](https://github.com/drevops/phpcs-standard/actions/workflows/test-php.yml/badge.svg)](https://github.com/drevops/phpcs-standard/actions/workflows/test-php.yml)
[![codecov](https://codecov.io/gh/drevops/phpcs-standard/graph/badge.svg?token=7WEB1IXBYT)](https://codecov.io/gh/drevops/phpcs-standard)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/drevops/phpcs-standard)
![LICENSE](https://img.shields.io/github/license/drevops/phpcs-standard)
![Renovate](https://img.shields.io/badge/renovate-enabled-green?logo=renovatebot)

</div>

---
PHP_CodeSniffer standard enforcing `snake_case` naming for local variables and function/method parameters. Class properties are intentionally excluded.

## Installation

```bash
composer require --dev drevops/phpcs-standard
```

The standard is automatically registered via [phpcodesniffer-composer-installer](https://github.com/PHPCSStandards/composer-installer).

Verify: `vendor/bin/phpcs -i` (should list `DrevOps`)

## Usage

```bash
# Check code
vendor/bin/phpcs --standard=DrevOps path/to/code

# Auto-fix
vendor/bin/phpcbf --standard=DrevOps path/to/code
```

## Configuration

Create `phpcs.xml`:

```xml
<?xml version="1.0"?>
<ruleset name="Project Standards">
  <rule ref="DrevOps"/>
  <file>src</file>
  <file>tests</file>
</ruleset>
```

Use individual sniffs:

```xml
<ruleset name="Custom Standards">
  <rule ref="DrevOps.NamingConventions.LocalVariableSnakeCase"/>
  <rule ref="DrevOps.NamingConventions.ParameterSnakeCase"/>
</ruleset>
```

## `LocalVariableSnakeCase`

Enforces `snake_case` for local variables inside functions/methods.

```php
function processOrder() {
    $order_id = 1;        // ✓ Valid
    $orderId = 1;         // ✗ Error: VariableNotSnakeCase
}
```

Excludes:
- Function/method parameters (handled by `ParameterSnakeCase`)
- Class properties (not enforced)
- Reserved variables (`$this`, `$_GET`, `$_POST`, etc.)

### Error code

`DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase`

### Ignore

```php
// phpcs:ignore DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase
$myVariable = 'value';
```

## `ParameterSnakeCase`

Enforces `snake_case` for function/method parameters.

```php
function processOrder($order_id, $user_data) {  // ✓ Valid
function processOrder($orderId, $userData) {    // ✗ Error: ParameterNotSnakeCase
```

Excludes:
- Parameters inherited from interfaces/parent classes
- Parameters in interface/abstract method declarations
- Class properties (including promoted constructor properties)

### Error code

`DrevOps.NamingConventions.ParameterSnakeCase.NotSnakeCase`

### Ignore

```php
// phpcs:ignore DrevOps.NamingConventions.ParameterSnakeCase.NotSnakeCase
function process($legacyParam) {}
```

## Development

```bash
composer install       # Install dependencies
composer test          # Run tests
composer test-coverage # Run tests with coverage
composer lint          # Check code standards
composer lint-fix      # Fix code standards
```

## License

GPL-3.0-or-later

---
_This repository was created using the [Scaffold](https://getphpcs-standard.dev/) project template_
