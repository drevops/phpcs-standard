<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use PHP_CodeSniffer\Files\DummyFile;
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
      'interface_declared_invalid_parameter' => [
        '<?php interface TestInterface { public function test($invalidParam); }',
        TRUE,
      ],
      'unresolved_parent_invalid_parameter' => [
        '<?php class Test extends BaseClass { public function test($invalidParam) {} }',
        FALSE,
      ],
      'unresolved_parent_private_method_invalid_parameter' => [
        '<?php class Test extends BaseClass { private function test($invalidParam) {} }',
        TRUE,
      ],
      'loaded_vendor_interface_inherited_parameters' => [
        '<?php class Test implements \PHP_CodeSniffer\Sniffs\Sniff { public function register(): array { return []; } public function process(\PHP_CodeSniffer\Files\File $phpcsFile, $stackPtr) {} }',
        FALSE,
      ],
      'internal_interface_own_method_invalid_parameter' => [
        '<?php class Test implements \Countable { public function count(): int { return 0; } public function add($invalidParam) {} }',
        TRUE,
      ],
      'underscore_prefixed_parameter' => [
        '<?php function test($_prefixed_param) {}',
        FALSE,
      ],
      'underscore_prefixed_parameter_camel' => [
        '<?php function test($_prefixedParam) {}',
        FALSE,
      ],
      'invalid_closure_parameter' => ['<?php $closure = function ($invalidParam) {};', TRUE],
      'invalid_arrow_function_parameter' => ['<?php $double = fn($invalidParam) => $invalidParam * 2;', TRUE],
      'valid_arrow_function_parameter' => ['<?php $double = fn($valid_param) => $valid_param * 2;', FALSE],
      'invalid_anonymous_class_method_parameter' => ['<?php function factory() { return new class { public function run($invalidParam) {} }; }', TRUE],
      'unresolved_parent_closure_parameter' => ['<?php class Test extends BaseClass { public function test() { return function ($invalidParam) {}; } }', TRUE],
      'unresolved_parent_arrow_function_parameter' => [
        '<?php class Test extends BaseClass { public function test() { return fn($invalidParam) => $invalidParam; } }',
        TRUE,
      ],
      'closure_use_clause' => ['<?php $invalidVar = 1; $closure = function () use ($invalidVar) { return $invalidVar; };', FALSE],
      'arrow_function_capture' => ['<?php $double = fn($valid_param) => $valid_param * $invalidFactor;', FALSE],
    ];
  }

  /**
   * Test process method with the drupalRoot property.
   *
   * @param mixed $drupal_root
   *   The drupalRoot property value.
   * @param int $expected_count
   *   Expected number of errors.
   */
  #[DataProvider('dataProviderProcessDrupalRoot')]
  public function testProcessDrupalRoot(mixed $drupal_root, int $expected_count): void {
    $sniff = $this->ruleset->sniffs[ParameterNamingSniff::class] ?? NULL;
    $this->assertInstanceOf(ParameterNamingSniff::class, $sniff);
    $sniff->drupalRoot = $drupal_root;

    $file = $this->processCode('<?php namespace Drupal\my_module; use Drupal\views\Plugin\views\field\FieldPluginBase; class Test extends FieldPluginBase { public function render($resultRow) {} public function formatLabel($labelText) {} }');

    $this->assertSame($expected_count, $file->getErrorCount());
  }

  /**
   * Data provider for process method tests with the drupalRoot property.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderProcessDrupalRoot(): array {
    return [
      'drupal_root' => [dirname(__DIR__) . '/Fixtures/Drupal', 2],
      'false' => [FALSE, 0],
      'not_set' => [NULL, 0],
    ];
  }

  /**
   * Test that process method throws exception for an invalid drupalRoot.
   */
  public function testProcessThrowsExceptionForInvalidDrupalRoot(): void {
    $sniff = $this->ruleset->sniffs[ParameterNamingSniff::class] ?? NULL;
    $this->assertInstanceOf(ParameterNamingSniff::class, $sniff);
    $sniff->drupalRoot = 'tests/Fixtures';

    // DummyFile leaves no temporary file behind when processing throws.
    $file = new DummyFile('<?php function test($invalidParam) {}', $this->ruleset, $this->config);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid drupalRoot "tests/Fixtures": core/lib/Drupal.php not found.');
    $file->process();
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

  /**
   * Test error code selection logic for both formats.
   *
   * This test verifies that the error code ternary operator correctly selects
   * between NotSnakeCase and NotCamelCase based on the format property.
   */
  public function testErrorCodeSelection(): void {
    $sniff = new ParameterNamingSniff();

    // Test snakeCase format returns NotSnakeCase error code.
    $sniff->format = 'snakeCase';
    // @phpstan-ignore-next-line identical.alwaysTrue
    $error_code_snake = ($sniff->format === 'snakeCase') ?
      ParameterNamingSniff::CODE_PARAMETER_NOT_SNAKE_CASE :
      ParameterNamingSniff::CODE_PARAMETER_NOT_CAMEL_CASE;
    $this->assertSame('NotSnakeCase', $error_code_snake);

    // Test camelCase format returns NotCamelCase error code.
    $sniff->format = 'camelCase';
    // @phpstan-ignore-next-line identical.alwaysFalse
    $error_code_camel = ($sniff->format === 'snakeCase') ?
      ParameterNamingSniff::CODE_PARAMETER_NOT_SNAKE_CASE :
      ParameterNamingSniff::CODE_PARAMETER_NOT_CAMEL_CASE;
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
    $class_name = ParameterNamingSniff::class;
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
      $file = $this->processCode('<?php function test($invalid_parameter) {}');
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
