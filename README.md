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

## Features

- Enforces `snake_case` naming for local variables and function/method
  parameters
- Intentionally excludes class properties from enforcement (properties can
  follow different conventions)
- Preserves inherited parameter names from interfaces and parent classes
- Provides helpful suggestions for converting variable names to `snake_case`
- Supports auto-fixing with `phpcbf` - automatically converts variables to
  snake_case
- Automatically registered as a PHPCS standard via Composer plugin

## Installation

    composer require --dev drevops/phpcs-standard

The standard will be automatically registered by
the [dealerdirect/phpcodesniffer-composer-installer](https://github.com/PHPCSStandards/composer-installer)
plugin.

Verify installation:

    vendor/bin/phpcs -i

You should see `DrevOps` in the list of installed coding standards.

## Usage

### Command Line

Check a file or directory:

    vendor/bin/phpcs --standard=DrevOps path/to/code

Check a specific file:

    vendor/bin/phpcs --standard=DrevOps src/MyClass.php

Auto-fix violations:

    vendor/bin/phpcbf --standard=DrevOps path/to/code

### Configuration File

Create a `phpcs.xml` file in your project root:

```xml
<?xml version="1.0"?>
<ruleset name="Project Coding Standards">
  <description>My project coding standards</description>

  <!-- Use the DrevOps standard -->
  <rule ref="DrevOps"/>

  <!-- Scan these directories -->
  <file>src</file>
  <file>tests</file>
</ruleset>
```

Then run:

    vendor/bin/phpcs

### Integrating with Existing Standards

You can combine the DrevOps standard with other standards:

```xml
<?xml version="1.0"?>
<ruleset name="My Standards">
  <!-- Include Drupal standards -->
  <rule ref="Drupal"/>

  <!-- Add DrevOps variable naming enforcement -->
  <rule ref="DrevOps.NamingConventions.VariableSnakeCase"/>
</ruleset>
```

## What Gets Checked

✅ **Checked (must be snake_case):**

- Local variables: `$user_name`, `$is_valid`, `$product_id`
- Function parameters: `function processOrder($order_id, $customer_data)`
- Method parameters: `public function save($entity_type, $entity_data)`
- Closure parameters: `array_map(function ($item_value) { ... }, $array)`

❌ **NOT Checked (ignored):**

- Class properties: `public $userName`, `private $customProperty`
- Static properties: `public static $instanceCount`
- Promoted constructor properties: `public function __construct(public string $propertyName)`
- Reserved PHP variables: `$this`, `$_GET`, `$_POST`, `$GLOBALS`, etc.

## Error Codes

- `DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase` - variable
  is not in snake_case format

You can ignore specific violations using:

```php
// phpcs:ignore DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase
$myVariable = 'value';
```

## Maintenance

Install dependencies:

    composer install

Run tests:

    composer test

Run linting:

    composer lint

Fix auto-fixable issues:

    composer lint-fix

## License

GPL-3.0-or-later

---
_This repository was created using the [Scaffold](https://getphpcs-standard.dev/) project template_
