<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\NamingConventions\AbstractSnakeCaseSniff;
use DrevOps\Sniffs\NamingConventions\LocalVariableSnakeCaseSniff;
use DrevOps\Sniffs\NamingConventions\ParameterSnakeCaseSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for AbstractSnakeCaseSniff.
 *
 * Tests all shared methods in the abstract base class using
 * LocalVariableSnakeCaseSniff as the concrete implementation.
 */
#[CoversClass(AbstractSnakeCaseSniff::class)]
class AbstractVariableSnakeCaseSniffTest extends UnitTestCase {

  /**
   * Test snake_case detection.
   *
   * @param string $name
   *   The variable name to test.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('providerSnakeCase')]
  public function testSnakeCaseDetection(string $name, bool $expected): void {
    $sniff = new LocalVariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isSnakeCase');

    $result = $method->invoke($sniff, $name);
    $this->assertSame($expected, $result, 'Failed for: ' . $name);
  }

  /**
   * Data provider for snake_case detection tests.
   *
   * @return array<string, array<string|bool>>
   *   Test cases.
   */
  public static function providerSnakeCase(): array {
    return [
      'valid_single_word' => ['test', TRUE],
      'valid_with_underscore' => ['test_variable', TRUE],
      'valid_with_number' => ['test123', TRUE],
      'valid_with_underscore_and_number' => ['test_123', TRUE],
      'valid_multiple_underscores' => ['test_long_variable_name', TRUE],
      'invalid_camelCase' => ['testVariable', FALSE],
      'invalid_PascalCase' => ['TestVariable', FALSE],
      'invalid_uppercase' => ['TEST', FALSE],
      'invalid_starting_uppercase' => ['Test', FALSE],
      'invalid_consecutive_underscores' => ['test__variable', FALSE],
      'invalid_leading_underscore' => ['_test', FALSE],
      'invalid_trailing_underscore' => ['test_', FALSE],
      'invalid_uppercase_with_underscore' => ['TEST_VAR', FALSE],
    ];
  }

  /**
   * Test snake_case conversion.
   *
   * @param string $input
   *   The input name.
   * @param string $expected
   *   Expected output.
   */
  #[DataProvider('providerToSnakeCase')]
  public function testToSnakeCase(string $input, string $expected): void {
    $sniff = new LocalVariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('toSnakeCase');

    $result = $method->invoke($sniff, $input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for toSnakeCase conversion tests.
   *
   * @return array<string, array<string>>
   *   Test cases.
   */
  public static function providerToSnakeCase(): array {
    return [
      'camelCase' => ['testVariable', 'test_variable'],
      'PascalCase' => ['TestVariable', 'test_variable'],
      'already_snake' => ['test_variable', 'test_variable'],
      'with_numbers' => ['test123Variable', 'test123_variable'],
      'consecutive_caps' => ['testHTMLParser', 'test_h_t_m_l_parser'],
      'leading_underscore' => ['_testVariable', 'test_variable'],
      'multiple_underscores' => ['test__variable', 'test_variable'],
    ];
  }

  /**
   * Test reserved variable detection.
   *
   * @param string $name
   *   The variable name.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('providerReservedVariables')]
  public function testReservedVariables(string $name, bool $expected): void {
    $sniff = new LocalVariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isReserved');

    $result = $method->invoke($sniff, $name);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for reserved variables.
   *
   * @return array<string, array<string|bool>>
   *   Test cases.
   */
  public static function providerReservedVariables(): array {
    return [
      'this' => ['this', TRUE],
      'GLOBALS' => ['GLOBALS', TRUE],
      '_SERVER' => ['_SERVER', TRUE],
      '_GET' => ['_GET', TRUE],
      '_POST' => ['_POST', TRUE],
      '_FILES' => ['_FILES', TRUE],
      '_COOKIE' => ['_COOKIE', TRUE],
      '_SESSION' => ['_SESSION', TRUE],
      '_REQUEST' => ['_REQUEST', TRUE],
      '_ENV' => ['_ENV', TRUE],
      'argv' => ['argv', TRUE],
      'argc' => ['argc', TRUE],
      'regular_var' => ['myVar', FALSE],
      'snake_var' => ['my_var', FALSE],
    ];
  }

  /**
   * Test register method.
   */
  public function testRegister(): void {
    $sniff = new LocalVariableSnakeCaseSniff();
    $tokens = $sniff->register();

    $this->assertContains(T_VARIABLE, $tokens);
  }

  /**
   * Test getParameterNames method.
   *
   * @param string $code
   *   PHP code to test.
   * @param array<string> $expected_params
   *   Expected parameter names.
   */
  #[DataProvider('providerGetParameterNames')]
  public function testGetParameterNames(string $code, array $expected_params): void {
    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);
    $sniff = new LocalVariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('getParameterNames');
    $result = $method->invoke($sniff, $file, $function_ptr);

    $this->assertIsArray($result);
    $this->assertCount(count($expected_params), $result);
    foreach ($expected_params as $param) {
      $this->assertContains($param, $result);
    }
  }

  /**
   * Data provider for getParameterNames tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function providerGetParameterNames(): array {
    return [
      'no_parameters' => [
        '<?php function test() {}',
        [],
      ],
      'single_parameter' => [
        '<?php function test($param) {}',
        ['$param'],
      ],
      'multiple_parameters' => [
        '<?php function test($param1, $param2, $param3) {}',
        ['$param1', '$param2', '$param3'],
      ],
    ];
  }

  /**
   * Test isProperty method.
   *
   * @param string $code
   *   PHP code to test.
   * @param string $variable_name
   *   Variable name to check.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('providerIsProperty')]
  public function testIsProperty(string $code, string $variable_name, bool $expected): void {
    $file = $this->processCode($code);
    $variable_ptr = $this->findVariableToken($file, $variable_name);
    $sniff = new LocalVariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isProperty');
    $result = $method->invoke($sniff, $file, $variable_ptr);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for isProperty tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function providerIsProperty(): array {
    return [
      'public_property' => [
        '<?php class Test { public $property; }',
        'property',
        TRUE,
      ],
      'private_property' => [
        '<?php class Test { private $property; }',
        'property',
        TRUE,
      ],
      'protected_property' => [
        '<?php class Test { protected $property; }',
        'property',
        TRUE,
      ],
      'static_property' => [
        '<?php class Test { public static $property; }',
        'property',
        TRUE,
      ],
      'local_variable' => [
        '<?php class Test { public function test() { $variable = 1; } }',
        'variable',
        FALSE,
      ],
      'parameter' => [
        '<?php function test($parameter) {}',
        'parameter',
        FALSE,
      ],
      'variable_in_class_body_not_property' => [
        '<?php class Test { const FOO = $bar; }',
        'bar',
        FALSE,
      ],
    ];
  }

  /**
   * Test isPromotedProperty method.
   *
   * @param string $code
   *   PHP code to test.
   * @param string $variable_name
   *   Variable name to check.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('providerIsPromotedProperty')]
  public function testIsPromotedProperty(string $code, string $variable_name, bool $expected): void {
    $file = $this->processCode($code);
    $variable_ptr = $this->findVariableToken($file, $variable_name);
    $sniff = new LocalVariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isPromotedProperty');
    $result = $method->invoke($sniff, $file, $variable_ptr);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for isPromotedProperty tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function providerIsPromotedProperty(): array {
    return [
      'promoted_public_property' => [
        '<?php class Test { public function __construct(public $property) {} }',
        'property',
        TRUE,
      ],
      'promoted_private_property' => [
        '<?php class Test { public function __construct(private $property) {} }',
        'property',
        TRUE,
      ],
      'promoted_protected_property' => [
        '<?php class Test { public function __construct(protected $property) {} }',
        'property',
        TRUE,
      ],
      'promoted_readonly_property' => [
        '<?php class Test { public function __construct(public readonly $property) {} }',
        'property',
        TRUE,
      ],
      'regular_parameter' => [
        '<?php class Test { public function __construct($parameter) {} }',
        'parameter',
        FALSE,
      ],
      'local_variable' => [
        '<?php class Test { public function test() { $variable = 1; } }',
        'variable',
        FALSE,
      ],
      'variable_at_file_start' => [
        '<?php $variable = 1;',
        'variable',
        FALSE,
      ],
    ];
  }

  /**
   * Test isInheritedParameter method.
   *
   * @param string $code
   *   PHP code to test.
   * @param string $variable_name
   *   Variable name to check.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('providerIsInheritedParameter')]
  public function testIsInheritedParameter(string $code, string $variable_name, bool $expected): void {
    $file = $this->processCode($code);
    $variable_ptr = $this->findVariableToken($file, $variable_name);
    $sniff = new ParameterSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isInheritedParameter');
    $result = $method->invoke($sniff, $file, $variable_ptr);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for isInheritedParameter tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function providerIsInheritedParameter(): array {
    return [
      'standalone_function' => [
        '<?php function test($parameter) {}',
        'parameter',
        FALSE,
      ],
      'interface_method' => [
        '<?php interface TestInterface { public function test($parameter); }',
        'parameter',
        TRUE,
      ],
      'abstract_method' => [
        '<?php abstract class Test { abstract public function test($parameter); }',
        'parameter',
        TRUE,
      ],
      'extending_class' => [
        '<?php class Test extends BaseClass { public function test($parameter) {} }',
        'parameter',
        TRUE,
      ],
      'implementing_class' => [
        '<?php class Test implements TestInterface { public function test($parameter) {} }',
        'parameter',
        TRUE,
      ],
      'regular_class_method' => [
        '<?php class Test { public function test($parameter) {} }',
        'parameter',
        FALSE,
      ],
      'extending_class_variable_in_body_matches_param' => [
        '<?php class Test extends BaseClass { public function test($parameter) { $parameter = 1; } }',
        'parameter',
        TRUE,
      ],
      'implementing_class_variable_in_body_matches_param' => [
        '<?php class Test implements TestInterface { public function test($parameter) { $parameter = 1; } }',
        'parameter',
        TRUE,
      ],
      'extending_class_variable_in_body_not_param' => [
        '<?php class Test extends BaseClass { public function test($parameter) { $other_var = 1; } }',
        'other_var',
        FALSE,
      ],
    ];
  }

  /**
   * Test isStaticPropertyAccess method.
   *
   * @param string $code
   *   PHP code to test.
   * @param string $variable_name
   *   Variable name to check.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('providerIsStaticPropertyAccess')]
  public function testIsStaticPropertyAccess(string $code, string $variable_name, bool $expected): void {
    $file = $this->processCode($code);
    $variable_ptr = $this->findVariableToken($file, $variable_name);
    $sniff = new LocalVariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isStaticPropertyAccess');
    $result = $method->invoke($sniff, $file, $variable_ptr);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for isStaticPropertyAccess tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function providerIsStaticPropertyAccess(): array {
    return [
      'self_static_property' => [
        '<?php class Test { public function test() { self::$property = 1; } }',
        'property',
        TRUE,
      ],
      'static_keyword_property' => [
        '<?php class Test { public function test() { static::$property = 1; } }',
        'property',
        TRUE,
      ],
      'class_name_property' => [
        '<?php class Test { public function test() { Test::$property = 1; } }',
        'property',
        TRUE,
      ],
      'parent_static_property' => [
        '<?php class Test { public function test() { parent::$property = 1; } }',
        'property',
        TRUE,
      ],
      'local_variable' => [
        '<?php class Test { public function test() { $variable = 1; } }',
        'variable',
        FALSE,
      ],
      'parameter' => [
        '<?php function test($parameter) {}',
        'parameter',
        FALSE,
      ],
      'property_declaration' => [
        '<?php class Test { public static $property; }',
        'property',
        FALSE,
      ],
    ];
  }

}
