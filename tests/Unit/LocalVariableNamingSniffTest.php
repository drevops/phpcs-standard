<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use DrevOps\Sniffs\NamingConventions\LocalVariableNamingSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for LocalVariableNamingSniff.
 *
 * Tests only sniff-specific logic. Abstract base class methods are tested
 * in AbstractVariableNamingSniffTest.
 */
#[CoversClass(LocalVariableNamingSniff::class)]
class LocalVariableNamingSniffTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();
    // Configure to run only LocalVariableNaming sniff.
    $this->config->sniffs = ['DrevOps.NamingConventions.LocalVariableNaming'];
    $this->ruleset = new Ruleset($this->config);
  }

  /**
   * Test error code constants.
   */
  public function testErrorCodeConstant(): void {
    $this->assertSame('NotSnakeCase', LocalVariableNamingSniff::CODE_VARIABLE_NOT_SNAKE_CASE);
    $this->assertSame('NotCamelCase', LocalVariableNamingSniff::CODE_VARIABLE_NOT_CAMEL_CASE);
  }

  /**
   * Test process method validates local variables.
   *
   * @param string $code
   *   PHP code to test.
   * @param bool $should_have_errors
   *   Whether errors should be detected.
   */
  #[DataProvider('dataProviderProcess')]
  public function testProcess(string $code, bool $should_have_errors): void {
    $file = $this->processCode($code);
    $errors = $file->getErrors();

    if ($should_have_errors) {
      $this->assertNotEmpty($errors);
    }
    else {
      $this->assertEmpty($errors);
    }
  }

  /**
   * Data provider for process method tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderProcess(): array {
    return [
      'valid_snake_case_variable' => [
        '<?php $valid_variable = 1;',
        FALSE,
      ],
      'invalid_camel_case_variable' => [
        '<?php $invalidVariable = 1;',
        TRUE,
      ],
      'reserved_variable' => [
        '<?php echo $_GET["key"];',
        FALSE,
      ],
      'class_property_camel_case' => [
        '<?php class Test { public $camelCaseProperty; }',
        FALSE,
      ],
      'local_variable_in_method_invalid' => [
        '<?php class Test { public function test() { $invalidVar = 1; } }',
        TRUE,
      ],
      'valid_parameter_name' => [
        '<?php function test($valid_param) {}',
        FALSE,
      ],
      'invalid_parameter_name' => [
        '<?php function test($invalidParam) {}',
        FALSE,
      ],
      'static_property_access_self' => [
        '<?php class Test { public function test() { self::$camelCaseProperty = 1; } }',
        FALSE,
      ],
      'static_property_access_static' => [
        '<?php class Test { public function test() { static::$camelCaseProperty = 1; } }',
        FALSE,
      ],
      'static_property_access_class_name' => [
        '<?php class Test { public function test() { Test::$camelCaseProperty = 1; } }',
        FALSE,
      ],
      'instance_property_access_this' => [
        '<?php class Test { public function test() { $this->camelCaseProperty = 1; } }',
        FALSE,
      ],
      'instance_property_access_object' => [
        '<?php class Test { public function test() { $obj = new self(); $obj->camelCaseProperty = 1; } }',
        FALSE,
      ],
      'instance_property_read' => [
        '<?php class Test { public function test() { $value = $this->camelCaseProperty; } }',
        FALSE,
      ],
    ];
  }

  /**
   * Test process method with camelCase format.
   *
   * @param string $code
   *   PHP code to test.
   * @param bool $should_have_errors
   *   Whether errors should be detected.
   */
  #[DataProvider('dataProviderProcessWithCamelCaseFormat')]
  public function testProcessWithCamelCaseFormat(string $code, bool $should_have_errors): void {
    // Create a new config with camelCase format property.
    $config = new Config();
    $config->standards = ['DrevOps'];
    $config->sniffs = ['DrevOps.NamingConventions.LocalVariableNaming'];

    // Create temporary ruleset XML with property configuration.
    $ruleset_xml = '<?xml version="1.0"?>
<ruleset name="Test">
    <rule ref="DrevOps.NamingConventions.LocalVariableNaming">
        <properties>
            <property name="format" value="camelCase"/>
        </properties>
    </rule>
</ruleset>';

    $ruleset_file = tempnam(sys_get_temp_dir(), 'phpcs_ruleset_');
    file_put_contents($ruleset_file, $ruleset_xml);

    try {
      $config->standards = [$ruleset_file];
      $this->ruleset = new Ruleset($config);

      $file = $this->processCode($code);
      $errors = $file->getErrors();

      if ($should_have_errors) {
        $this->assertNotEmpty($errors);
      }
      else {
        $this->assertEmpty($errors);
      }
    }
    finally {
      unlink($ruleset_file);
    }
  }

  /**
   * Data provider for camelCase format tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderProcessWithCamelCaseFormat(): array {
    return [
      'valid_camel_case_variable' => [
        '<?php $validVariable = 1;',
        FALSE,
      ],
      'invalid_snake_case_variable' => [
        '<?php $invalid_variable = 1;',
        TRUE,
      ],
      'single_word_valid' => [
        '<?php $test = 1;',
        FALSE,
      ],
      'local_variable_in_method_valid' => [
        '<?php class Test { public function test() { $validVar = 1; } }',
        FALSE,
      ],
      'local_variable_in_method_invalid' => [
        '<?php class Test { public function test() { $invalid_var = 1; } }',
        TRUE,
      ],
    ];
  }

}
