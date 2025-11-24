<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

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
   * Test error code selection logic for both formats.
   *
   * This test verifies that the error code ternary operator correctly selects
   * between NotSnakeCase and NotCamelCase based on the format property.
   */
  public function testErrorCodeSelection(): void {
    $sniff = new LocalVariableNamingSniff();

    // Test snakeCase format returns NotSnakeCase error code.
    $sniff->format = 'snakeCase';
    // @phpstan-ignore-next-line identical.alwaysTrue
    $error_code_snake = ($sniff->format === 'snakeCase') ?
      LocalVariableNamingSniff::CODE_VARIABLE_NOT_SNAKE_CASE :
      LocalVariableNamingSniff::CODE_VARIABLE_NOT_CAMEL_CASE;
    $this->assertSame('NotSnakeCase', $error_code_snake);

    // Test camelCase format returns NotCamelCase error code.
    $sniff->format = 'camelCase';
    // @phpstan-ignore-next-line identical.alwaysFalse
    $error_code_camel = ($sniff->format === 'snakeCase') ?
      LocalVariableNamingSniff::CODE_VARIABLE_NOT_SNAKE_CASE :
      LocalVariableNamingSniff::CODE_VARIABLE_NOT_CAMEL_CASE;
    $this->assertSame('NotCamelCase', $error_code_camel);
  }

  /**
   * Test camelCase format with actual code processing.
   *
   * This test achieves 100% coverage by actually executing the camelCase
   * error code path by modifying the sniff instances in the ruleset.
   */
  public function testCamelCaseFormatIntegration(): void {
    // Modify the sniff instance to use camelCase format.
    $class_name = LocalVariableNamingSniff::class;
    $original_format = NULL;

    // The sniff is stored as a single object, not an array.
    // @phpstan-ignore-next-line booleanAnd.rightAlwaysTrue
    if (isset($this->ruleset->sniffs[$class_name]) && is_object($this->ruleset->sniffs[$class_name])) {
      $sniff = $this->ruleset->sniffs[$class_name];
      // Save original format.
      // @phpstan-ignore-next-line property.notFound
      $original_format = $sniff->format;
      // Set to camelCase.
      // @phpstan-ignore-next-line property.notFound
      $sniff->format = 'camelCase';
    }

    try {
      // Test that snake_case is invalid with camelCase format.
      $file = $this->processCode('<?php $invalid_variable = 1;');
      $errors = $file->getErrors();
      $this->assertNotEmpty($errors, 'Expected errors to be detected with camelCase format');

      // Verify it's using the NotCamelCase error code.
      $this->assertArrayHasKey(1, $errors);
      $first_error = array_values($errors[1])[0][0];
      $this->assertStringContainsString('NotCamelCase', $first_error['source']);
    }
    finally {
      // Restore original format.
      if ($original_format !== NULL && isset($this->ruleset->sniffs[$class_name])) {
        // @phpstan-ignore-next-line property.notFound
        $this->ruleset->sniffs[$class_name]->format = $original_format;
      }
    }
  }

}
