<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Ruleset;
use DrevOps\Sniffs\NamingConventions\ParameterNamingSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for ParameterNamingSniff.
 *
 * Tests sniff-specific logic. Shared base class methods are tested
 * in AbstractVariableNamingSniffTest.
 */
#[CoversClass(ParameterNamingSniff::class)]
class ParameterNamingSniffTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();
    // Configure to run only ParameterNaming sniff.
    $this->config->sniffs = ['DrevOps.NamingConventions.ParameterNaming'];
    $this->ruleset = new Ruleset($this->config);
  }

  /**
   * Test that the sniff registers the correct token types.
   */
  public function testRegister(): void {
    $sniff = new ParameterNamingSniff();
    $tokens = $sniff->register();

    $this->assertContains(T_VARIABLE, $tokens);
  }

  /**
   * Test error code constants.
   */
  public function testErrorCodeConstant(): void {
    $this->assertSame('NotSnakeCase', ParameterNamingSniff::CODE_PARAMETER_NOT_SNAKE_CASE);
    $this->assertSame('NotCamelCase', ParameterNamingSniff::CODE_PARAMETER_NOT_CAMEL_CASE);
  }

  /**
   * Test process method validates parameters.
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
        FALSE,
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
        FALSE,
      ],
      'valid_parameter_name' => [
        '<?php function test($valid_param) {}',
        FALSE,
      ],
      'invalid_parameter_name' => [
        '<?php function test($invalidParam) {}',
        TRUE,
      ],
      'inherited_invalid_parameter_interface' => [
        '<?php interface TestInterface { public function test($invalidParam); }',
        FALSE,
      ],
      'inherited_invalid_parameter_extends' => [
        '<?php class Test extends BaseClass { public function test($invalidParam) {} }',
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
    $config->sniffs = ['DrevOps.NamingConventions.ParameterNaming'];

    // Create temporary ruleset XML with property configuration.
    $ruleset_xml = '<?xml version="1.0"?>
<ruleset name="Test">
    <rule ref="DrevOps.NamingConventions.ParameterNaming">
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
      'valid_camel_case_parameter' => [
        '<?php function test($validParameter) {}',
        FALSE,
      ],
      'invalid_snake_case_parameter' => [
        '<?php function test($invalid_parameter) {}',
        TRUE,
      ],
      'single_word_valid' => [
        '<?php function test($param) {}',
        FALSE,
      ],
      'method_parameter_valid' => [
        '<?php class Test { public function test($validParam) {} }',
        FALSE,
      ],
      'method_parameter_invalid' => [
        '<?php class Test { public function test($invalid_param) {} }',
        TRUE,
      ],
    ];
  }

  /**
   * Test findFunctionDocblock method finds docblock for a function.
   *
   * @param string $code
   *   PHP code to test.
   * @param bool $should_find_docblock
   *   Whether a docblock should be found.
   */
  #[DataProvider('providerFindFunctionDocblock')]
  public function testFindFunctionDocblock(string $code, bool $should_find_docblock): void {
    $file = $this->processCode($code);
    $sniff = new ParameterNamingSniff();

    // Use reflection to access the private method.
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('findFunctionDocblock');

    // Find the function token.
    $function_token = $this->findFunctionToken($file);
    $this->assertNotFalse($function_token, 'Function token should be found');

    // Call the method.
    $result = $method->invoke($sniff, $file, $function_token);

    if ($should_find_docblock) {
      $this->assertNotFalse($result, 'Docblock should be found');
      $this->assertIsInt($result);
    }
    else {
      $this->assertFalse($result, 'Docblock should not be found');
    }
  }

  /**
   * Data provider for findFunctionDocblock tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function providerFindFunctionDocblock(): array {
    return [
      'function_with_docblock' => [
        '<?php
        /**
         * Test function.
         *
         * @param string $param
         *   A parameter.
         */
        function testFunction($param) {}',
        TRUE,
      ],
      'function_without_docblock' => [
        '<?php function testFunction($param) {}',
        FALSE,
      ],
      'function_with_single_line_comment' => [
        '<?php
        // This is a comment.
        function testFunction($param) {}',
        FALSE,
      ],
      'method_with_docblock_and_visibility' => [
        '<?php
        class Test {
          /**
           * Test method.
           *
           * @param string $param
           *   A parameter.
           */
          public function testFunction($param) {}
        }',
        TRUE,
      ],
    ];
  }

}
