<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\NamingConventions\AbstractVariableNamingSniff;
use DrevOps\Sniffs\NamingConventions\LocalVariableNamingSniff;
use DrevOps\Sniffs\NamingConventions\ParameterNamingSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for AbstractVariableNamingSniff.
 *
 * Tests all shared methods in the abstract base class using
 * LocalVariableNamingSniff as the concrete implementation.
 */
#[CoversClass(AbstractVariableNamingSniff::class)]
class AbstractVariableNamingSniffTest extends UnitTestCase {

  /**
   * Test snake_case detection.
   *
   * @param string $name
   *   The variable name to test.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('dataProviderSnakeCaseDetection')]
  public function testSnakeCaseDetection(string $name, bool $expected): void {
    $sniff = new LocalVariableNamingSniff();
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
  public static function dataProviderSnakeCaseDetection(): array {
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
    $sniff = new LocalVariableNamingSniff();
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
    $sniff = new LocalVariableNamingSniff();
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
    $sniff = new LocalVariableNamingSniff();
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
   * @param int|string $function_code
   *   The code of the token to read the names of; its first occurrence is used.
   */
  #[DataProvider('dataProviderGetParameterNames')]
  public function testGetParameterNames(string $code, array $expected_params, int|string $function_code = T_FUNCTION): void {
    $file = $this->processCode($code);
    $function_ptr = $this->findTokenByCode($file, $function_code);
    $sniff = new LocalVariableNamingSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('getParameterNames');
    $result = $method->invoke($sniff, $file, $function_ptr);

    $this->assertSame($expected_params, $result);
  }

  /**
   * Data provider for getParameterNames tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderGetParameterNames(): array {
    return [
      'no_parameters' => ['<?php function test() {}', []],
      'single_parameter' => ['<?php function test($param) {}', ['$param']],
      'multiple_parameters' => ['<?php function test($param1, $param2, $param3) {}', ['$param1', '$param2', '$param3']],
      'reference_and_variadic_parameters' => ['<?php function test(int &$first, string ...$rest) {}', ['$first', '$rest']],
      'promoted_property' => ['<?php class Test { public function __construct(public $promoted, $plain) {} }', ['$promoted', '$plain']],
      'closure' => ['<?php $closure = function ($first, $second) use ($outer) {};', ['$first', '$second'], T_CLOSURE],
      'closure_use_clause' => ['<?php $closure = function ($first) use ($outer, &$total) {};', ['$outer', '$total'], T_USE],
      'closure_use_clause_without_parentheses' => ['<?php $closure = function () use { return $outer; };', [], T_USE],
      'arrow_function' => ['<?php $add = fn($first, $second) => $first + $second;', ['$first', '$second'], T_FN],
      'closure_in_default_value' => ['<?php function test($callback = static function ($inner) { return $inner; }, $after = 1) {}', ['$callback', '$after']],
    ];
  }

  /**
   * Test findParameterListOwner method.
   *
   * @param string $code
   *   PHP code to test.
   * @param string $variable_name
   *   Variable name to check.
   * @param int $occurrence
   *   Which occurrence of the variable to check, starting at 1.
   * @param array{0: int|string, 1: int}|null $expected
   *   The code and occurrence of the expected owner token, or NULL when the
   *   variable is not in a parameter list.
   */
  #[DataProvider('dataProviderFindParameterListOwner')]
  public function testFindParameterListOwner(string $code, string $variable_name, int $occurrence, ?array $expected): void {
    $this->assertFunctionLookup('findParameterListOwner', $code, $variable_name, $occurrence, $expected);
  }

  /**
   * Data provider for findParameterListOwner tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderFindParameterListOwner(): array {
    $function = '<?php function test($parameter) { return strlen($parameter); }';
    $closure = '<?php class Test { public function test() { $closure = function ($parameter) { return $parameter; }; } }';
    $arrow_function = '<?php class Test { public function test() { $double = fn($parameter) => $parameter * 2; } }';
    $nested_arrow_functions = '<?php $add = fn($first) => fn($second) => $first + $second;';
    $anonymous_class = '<?php function factory() { return new class { public function run($parameter) {} }; }';
    $call_argument = '<?php array_map(function ($item) { return strlen($item); }, []);';
    $default_value = '<?php function test($callback = static function ($inner) { return $inner; }, $after = 1) {}';
    $attribute = '<?php function test(#[Attr(static function ($inner) { return $inner; })] $attributed) {}';

    return [
      'function_parameter' => [$function, 'parameter', 1, [T_FUNCTION, 1]],
      'call_argument' => [$function, 'parameter', 2, NULL],
      'method_parameter' => ['<?php class Test { public function test($parameter) {} }', 'parameter', 1, [T_FUNCTION, 1]],
      'control_structure_condition' => ['<?php function test($parameter) { if ($parameter) {} }', 'parameter', 2, NULL],
      'closure_parameter' => [$closure, 'parameter', 1, [T_CLOSURE, 1]],
      'closure_body' => [$closure, 'parameter', 2, NULL],
      'closure_use_clause' => ['<?php function test($parameter) { return function () use ($parameter) {}; }', 'parameter', 2, NULL],
      'arrow_function_parameter' => [$arrow_function, 'parameter', 1, [T_FN, 1]],
      'arrow_function_body' => [$arrow_function, 'parameter', 2, NULL],
      'reference_arrow_function_parameter' => ['<?php $get = fn&(array &$parameter) => $parameter;', 'parameter', 1, [T_FN, 1]],
      'nested_arrow_function_parameter' => [$nested_arrow_functions, 'second', 1, [T_FN, 2]],
      'anonymous_class_method_parameter' => [$anonymous_class, 'parameter', 1, [T_FUNCTION, 2]],
      'anonymous_class_argument' => ['<?php function test($parameter) { return new class($parameter) {}; }', 'parameter', 2, NULL],
      'closure_parameter_in_call_argument' => [$call_argument, 'item', 1, [T_CLOSURE, 1]],
      'closure_body_in_call_argument' => [$call_argument, 'item', 2, NULL],
      'global_variable' => ['<?php $variable = 1;', 'variable', 1, NULL],
      'closure_parameter_in_default_value' => [$default_value, 'inner', 1, [T_CLOSURE, 1]],
      'closure_body_in_default_value' => [$default_value, 'inner', 2, NULL],
      'parameter_after_closure_in_default_value' => [$default_value, 'after', 1, [T_FUNCTION, 1]],
      'closure_parameter_in_attribute' => [$attribute, 'inner', 1, [T_CLOSURE, 1]],
      'closure_body_in_attribute' => [$attribute, 'inner', 2, NULL],
      'parameter_after_closure_in_attribute' => [$attribute, 'attributed', 1, [T_FUNCTION, 1]],
    ];
  }

  /**
   * Test findEnclosingFunction method.
   *
   * @param string $code
   *   PHP code to test.
   * @param string $variable_name
   *   Variable name to check.
   * @param int $occurrence
   *   Which occurrence of the variable to check, starting at 1.
   * @param array{0: int|string, 1: int}|null $expected
   *   The code and occurrence of the expected function token, or NULL when the
   *   variable is outside function bodies.
   */
  #[DataProvider('dataProviderFindEnclosingFunction')]
  public function testFindEnclosingFunction(string $code, string $variable_name, int $occurrence, ?array $expected): void {
    $this->assertFunctionLookup('findEnclosingFunction', $code, $variable_name, $occurrence, $expected);
  }

  /**
   * Data provider for findEnclosingFunction tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderFindEnclosingFunction(): array {
    $method = '<?php class Test { public function test($parameter) { $variable = 1; } }';
    $closure = '<?php class Test { public function test() { $closure = function ($parameter) { return $parameter; }; } }';
    $arrow_function = '<?php function test() { $double = fn($parameter) => $parameter * 2; return $parameter; }';
    $nested_arrow_functions = '<?php $add = fn($first) => fn($second) => $first + $second;';
    $closure_in_arrow_function = '<?php $make = fn($outer) => function () use ($outer) { return $outer; };';
    $anonymous_class = '<?php function factory() { return new class { public $property; public function run() { $variable = 1; } }; }';

    return [
      'global_variable' => ['<?php $variable = 1;', 'variable', 1, NULL],
      'global_variable_after_function' => ['<?php function test($variable) {} $variable = 1;', 'variable', 2, NULL],
      'function_parameter' => ['<?php function test($parameter) {}', 'parameter', 1, NULL],
      'function_body' => ['<?php function test() { $variable = 1; }', 'variable', 1, [T_FUNCTION, 1]],
      'method_parameter' => [$method, 'parameter', 1, NULL],
      'method_body' => [$method, 'variable', 1, [T_FUNCTION, 1]],
      'closure_parameter' => [$closure, 'parameter', 1, [T_FUNCTION, 1]],
      'closure_body' => [$closure, 'parameter', 2, [T_CLOSURE, 1]],
      'closure_use_clause' => ['<?php function test($parameter) { return function () use ($parameter) {}; }', 'parameter', 2, [T_FUNCTION, 1]],
      'arrow_function_parameter' => [$arrow_function, 'parameter', 1, [T_FUNCTION, 1]],
      'arrow_function_body' => [$arrow_function, 'parameter', 2, [T_FN, 1]],
      'after_arrow_function' => [$arrow_function, 'parameter', 3, [T_FUNCTION, 1]],
      'arrow_function_in_global_code' => ['<?php $double = fn($parameter) => $parameter * 2;', 'parameter', 2, [T_FN, 1]],
      'arrow_function_parameter_in_arrow_function' => [$nested_arrow_functions, 'second', 1, [T_FN, 1]],
      'nested_arrow_function_body' => [$nested_arrow_functions, 'first', 2, [T_FN, 2]],
      'match_in_arrow_function' => ['<?php $pick = fn($key) => match ($key) { 1 => $key, default => NULL };', 'key', 3, [T_FN, 1]],
      'closure_use_clause_in_arrow_function' => [$closure_in_arrow_function, 'outer', 2, [T_FN, 1]],
      'closure_in_arrow_function' => [$closure_in_arrow_function, 'outer', 3, [T_CLOSURE, 1]],
      'arrow_function_in_closure' => ['<?php $make = function ($outer) { return fn($inner) => $inner + $outer; };', 'outer', 2, [T_FN, 1]],
      'anonymous_class_property' => [$anonymous_class, 'property', 1, NULL],
      'anonymous_class_method_body' => [$anonymous_class, 'variable', 1, [T_FUNCTION, 2]],
      'anonymous_class_property_in_arrow_function' => ['<?php $make = fn() => new class { public $property; };', 'property', 1, NULL],
    ];
  }

  /**
   * Test findDeclaringFunction method.
   *
   * @param string $code
   *   PHP code to test.
   * @param string $variable_name
   *   Variable name to check.
   * @param int $occurrence
   *   Which occurrence of the variable to check, starting at 1.
   * @param array{0: int|string, 1: int}|null $expected
   *   The code and occurrence of the expected function token, or NULL when the
   *   variable is not a parameter.
   */
  #[DataProvider('dataProviderFindDeclaringFunction')]
  public function testFindDeclaringFunction(string $code, string $variable_name, int $occurrence, ?array $expected): void {
    $this->assertFunctionLookup('findDeclaringFunction', $code, $variable_name, $occurrence, $expected);
  }

  /**
   * Data provider for findDeclaringFunction tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderFindDeclaringFunction(): array {
    $function = '<?php function test($parameter) { $local = $parameter; }';
    $closure = '<?php class Test { public function test() { $closure = function ($parameter) { return $parameter; }; } }';
    $arrow_function = '<?php class Test { public function test() { $double = fn($parameter) => $parameter * 2; } }';
    $anonymous_class = '<?php function factory() { return new class { public function run($parameter) { return $parameter; } }; }';
    $use = '<?php class Test { public function test($parameter) { return function () use ($parameter) { return $parameter; }; } }';
    $reference_use = '<?php function test(array $parameter) { return function () use (&$parameter) { $parameter[] = 1; }; }';
    $capture = '<?php class Test { public function test($parameter) { return fn($item_value) => $item_value . $parameter; } }';
    $nested_arrow_functions = '<?php function test($parameter) { return fn($first) => fn($second) => $parameter + $first + $second; }';
    $shadowed = '<?php class Test { public function test($parameter) { return function ($parameter) { return $parameter; }; } }';
    $anonymous_class_outer_name = '<?php function factory($parameter) { return new class { public function run() { return $parameter; } }; }';

    return [
      'function_parameter' => [$function, 'parameter', 1, [T_FUNCTION, 1]],
      'function_body' => [$function, 'parameter', 2, [T_FUNCTION, 1]],
      'function_local' => [$function, 'local', 1, NULL],
      'global_variable' => ['<?php $variable = 1;', 'variable', 1, NULL],
      'global_variable_named_like_earlier_parameter' => ['<?php function test($variable) {} $variable = 1;', 'variable', 2, NULL],
      'closure_body' => [$closure, 'parameter', 2, [T_CLOSURE, 1]],
      'arrow_function_body' => [$arrow_function, 'parameter', 2, [T_FN, 1]],
      'anonymous_class_method_body' => [$anonymous_class, 'parameter', 2, [T_FUNCTION, 2]],
      'use_clause' => [$use, 'parameter', 2, [T_FUNCTION, 1]],
      'imported_with_use' => [$use, 'parameter', 3, [T_FUNCTION, 1]],
      'imported_by_reference' => [$reference_use, 'parameter', 3, [T_FUNCTION, 1]],
      'not_imported_by_closure' => ['<?php function test($parameter) { return function () { return $parameter; }; }', 'parameter', 2, NULL],
      'captured_by_arrow_function' => [$capture, 'parameter', 2, [T_FUNCTION, 1]],
      'arrow_function_local' => ['<?php function test() { return fn() => $local = 1; }', 'local', 1, NULL],
      'arrow_function_in_global_code' => ['<?php $double = fn($value) => $value * $factor;', 'factor', 1, NULL],
      'captured_across_nested_arrow_functions' => [$nested_arrow_functions, 'parameter', 2, [T_FUNCTION, 1]],
      'captured_from_outer_arrow_function' => [$nested_arrow_functions, 'first', 2, [T_FN, 1]],
      'closure_in_arrow_function' => ['<?php $make = fn($outer) => function () use ($outer) { return $outer; };', 'outer', 3, [T_FN, 1]],
      'arrow_function_in_closure' => ['<?php $make = function ($outer) { return fn($inner) => $inner + $outer; };', 'outer', 2, [T_CLOSURE, 1]],
      'closure_parameter_shadows_method_parameter' => [$shadowed, 'parameter', 3, [T_CLOSURE, 1]],
      'anonymous_class_method_does_not_capture' => [$anonymous_class_outer_name, 'parameter', 2, NULL],
      'nested_function_does_not_capture' => ['<?php function outer($parameter) { function inner() { return $parameter; } }', 'parameter', 2, NULL],
    ];
  }

  /**
   * Test capturesVariable method.
   *
   * @param string $code
   *   PHP code to test.
   * @param int|string $function_code
   *   The code of the function token to check; its first occurrence is used.
   * @param string $variable_name
   *   Variable name to check, including the leading '$'.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('dataProviderCapturesVariable')]
  public function testCapturesVariable(string $code, int|string $function_code, string $variable_name, bool $expected): void {
    $file = $this->processCode($code);
    $function_ptr = $this->findTokenByCode($file, $function_code);
    $sniff = new LocalVariableNamingSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('capturesVariable');
    $result = $method->invoke($sniff, $file, $function_ptr, $variable_name);

    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for capturesVariable tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderCapturesVariable(): array {
    $use = '<?php $closure = function () use ($factor) {};';

    return [
      'arrow_function' => ['<?php $double = fn($value) => $value * $factor;', T_FN, '$factor', TRUE],
      'closure_use_clause' => [$use, T_CLOSURE, '$factor', TRUE],
      'closure_use_clause_other_variable' => [$use, T_CLOSURE, '$other', FALSE],
      'closure_use_clause_by_reference' => ['<?php $closure = function () use (&$total) {};', T_CLOSURE, '$total', TRUE],
      'closure_use_clause_with_return_type' => ['<?php $closure = function ($value) use ($factor): int { return 1; };', T_CLOSURE, '$factor', TRUE],
      'closure_use_clause_after_comment' => ['<?php $closure = function () /* Imports. */ use ($factor) {};', T_CLOSURE, '$factor', TRUE],
      'closure_use_clause_without_parentheses' => ['<?php $closure = function () use { return $factor; };', T_CLOSURE, '$factor', FALSE],
      'closure_parameter' => ['<?php $closure = function ($factor) {};', T_CLOSURE, '$factor', FALSE],
      'closure_with_nested_use_clause' => ['<?php $closure = function () { return function () use ($factor) {}; };', T_CLOSURE, '$factor', FALSE],
      'function' => ['<?php function test() { return $factor; }', T_FUNCTION, '$factor', FALSE],
    ];
  }

  /**
   * Test isParameter method.
   *
   * @param string $code
   *   PHP code to test.
   * @param string $variable_name
   *   Variable name to check.
   * @param int $occurrence
   *   Which occurrence of the variable to check, starting at 1.
   * @param bool $include_usage_in_body
   *   Whether uses in a body count.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('dataProviderIsParameter')]
  public function testIsParameter(string $code, string $variable_name, int $occurrence, bool $include_usage_in_body, bool $expected): void {
    $file = $this->processCode($code);
    $variable_ptr = $this->findVariableToken($file, $variable_name, $occurrence);
    $sniff = new LocalVariableNamingSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isParameter');
    $result = $method->invoke($sniff, $file, $variable_ptr, $include_usage_in_body);

    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for isParameter tests.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderIsParameter(): array {
    $function = '<?php function test($parameter) { $local = $parameter; }';
    $promoted = '<?php class Test { public function __construct(public $parameter) {} }';
    $closure = '<?php class Test { public function test(): void { $closure = function ($parameter) { return $parameter; }; } }';
    $arrow_function = '<?php class Test { public function test(): void { $double = fn($parameter) => $parameter * 2; } }';
    $anonymous_class = '<?php function factory() { return new class { public function run($parameter) { return $parameter; } }; }';
    $use = '<?php class Test { public function test($parameter) { return function () use ($parameter) { return $parameter; }; } }';
    $capture = '<?php class Test { public function test($parameter) { return fn($item_value) => $item_value . $parameter; } }';
    $not_imported = '<?php class Test { public function test($parameter) { return function () { return $parameter; }; } }';
    $anonymous_class_outer_name = '<?php function factory($parameter) { return new class { public function run() { return $parameter; } }; }';
    $global = '<?php function test($parameter) {} $parameter = 1;';

    return [
      'function_signature' => [$function, 'parameter', 1, FALSE, TRUE],
      'function_signature_with_body' => [$function, 'parameter', 1, TRUE, TRUE],
      'function_body' => [$function, 'parameter', 2, FALSE, FALSE],
      'function_body_with_body' => [$function, 'parameter', 2, TRUE, TRUE],
      'function_local' => [$function, 'local', 1, FALSE, FALSE],
      'function_local_with_body' => [$function, 'local', 1, TRUE, FALSE],
      'promoted_property' => [$promoted, 'parameter', 1, FALSE, FALSE],
      'promoted_property_with_body' => [$promoted, 'parameter', 1, TRUE, FALSE],
      'closure_signature' => [$closure, 'parameter', 1, FALSE, TRUE],
      'closure_body' => [$closure, 'parameter', 2, FALSE, FALSE],
      'closure_body_with_body' => [$closure, 'parameter', 2, TRUE, TRUE],
      'arrow_function_signature' => [$arrow_function, 'parameter', 1, FALSE, TRUE],
      'arrow_function_signature_with_body' => [$arrow_function, 'parameter', 1, TRUE, TRUE],
      'arrow_function_body' => [$arrow_function, 'parameter', 2, FALSE, FALSE],
      'arrow_function_body_with_body' => [$arrow_function, 'parameter', 2, TRUE, TRUE],
      'anonymous_class_method_signature' => [$anonymous_class, 'parameter', 1, FALSE, TRUE],
      'anonymous_class_method_body_with_body' => [$anonymous_class, 'parameter', 2, TRUE, TRUE],
      'use_clause' => [$use, 'parameter', 2, FALSE, FALSE],
      'use_clause_with_body' => [$use, 'parameter', 2, TRUE, TRUE],
      'imported_with_use' => [$use, 'parameter', 3, FALSE, FALSE],
      'imported_with_use_with_body' => [$use, 'parameter', 3, TRUE, TRUE],
      'captured_by_arrow_function' => [$capture, 'parameter', 2, FALSE, FALSE],
      'captured_by_arrow_function_with_body' => [$capture, 'parameter', 2, TRUE, TRUE],
      'not_imported_by_closure_with_body' => [$not_imported, 'parameter', 2, TRUE, FALSE],
      'anonymous_class_method_outer_name_with_body' => [$anonymous_class_outer_name, 'parameter', 2, TRUE, FALSE],
      'global_variable_named_like_earlier_parameter_with_body' => [$global, 'parameter', 2, TRUE, FALSE],
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
    $sniff = new LocalVariableNamingSniff();
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
      'typed_property' => [
        '<?php class Test { protected string $typedProperty; }',
        'typedProperty',
        TRUE,
      ],
      'nullable_property' => [
        '<?php class Test { protected ?string $nullableProperty = NULL; }',
        'nullableProperty',
        TRUE,
      ],
      'nullable_fully_qualified_property' => [
        '<?php class Test { protected ?\DOMDocument $xmlDom = NULL; }',
        'xmlDom',
        TRUE,
      ],
      'nullable_qualified_property' => [
        '<?php namespace App; class Test { protected ?Some\Type $property = NULL; }',
        'property',
        TRUE,
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
    $sniff = new LocalVariableNamingSniff();
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
      'promoted_nullable_property' => [
        '<?php class Test { public function __construct(public ?string $property) {} }',
        'property',
        TRUE,
      ],
      'promoted_nullable_fully_qualified_property' => [
        '<?php class Test { public function __construct(public ?\DOMDocument $dom) {} }',
        'dom',
        TRUE,
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
   * @param int $occurrence
   *   Which occurrence of the variable to check, starting at 1.
   */
  #[DataProvider('dataProviderIsInheritedParameter')]
  public function testIsInheritedParameter(string $code, string $variable_name, bool $expected, int $occurrence = 1): void {
    $file = $this->processCode($code);
    $variable_ptr = $this->findVariableToken($file, $variable_name, $occurrence);
    $sniff = new ParameterNamingSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isInheritedParameter');
    $result = $method->invoke($sniff, $file, $variable_ptr);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for isInheritedParameter tests.
   *
   * Child classes are declared before their same-file ancestors, so the
   * occurrences of a variable are counted from the child.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderIsInheritedParameter(): array {
    return [
      'standalone_function' => [
        '<?php function test($parameter) {}',
        'parameter',
        FALSE,
      ],
      'variable_outside_function' => [
        '<?php $parameter = 1;',
        'parameter',
        FALSE,
      ],
      'interface_method' => [
        '<?php interface TestInterface { public function test($parameter); }',
        'parameter',
        FALSE,
      ],
      'interface_method_redeclared_from_parent_interface' => [
        '<?php interface ChildInterface extends ParentInterface { public function test($parameter); } interface ParentInterface { public function test($parameter); }',
        'parameter',
        TRUE,
      ],
      'interface_method_with_unresolved_parent_interface' => [
        '<?php interface ChildInterface extends MissingInterface { public function test($parameter); }',
        'parameter',
        TRUE,
      ],
      'abstract_method' => [
        '<?php abstract class Test { abstract public function test($parameter); }',
        'parameter',
        FALSE,
      ],
      'method_after_abstract_method' => [
        '<?php abstract class Test { abstract public function first(); public function test($parameter) {} }',
        'parameter',
        FALSE,
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
      'overriding_same_file_parent_method' => [
        '<?php class Test extends Base { public function test($parameter) {} } class Base { public function test($parameter) {} }',
        'parameter',
        TRUE,
      ],
      'own_method_with_same_file_parent' => [
        '<?php class Test extends Base { public function own($parameter) {} } class Base { public function test($parameter) {} }',
        'parameter',
        FALSE,
      ],
      'renamed_parameter_of_same_file_parent_method' => [
        '<?php class Test extends Base { public function test($renamed) {} } class Base { public function test($parameter) {} }',
        'renamed',
        FALSE,
      ],
      'private_method_with_unresolved_parent' => [
        '<?php class Test extends BaseClass { private function test($parameter) {} }',
        'parameter',
        FALSE,
      ],
      'closure_in_method_of_extending_class' => [
        '<?php class Test extends BaseClass { public function test() { $closure = function ($parameter) {}; } }',
        'parameter',
        FALSE,
      ],
      'internal_interface_method' => [
        '<?php class Test implements \ArrayAccess { public function offsetGet(mixed $offset): mixed {} }',
        'offset',
        TRUE,
      ],
      'own_method_with_internal_interface' => [
        '<?php class Test implements \Countable { public function add($parameter) {} }',
        'parameter',
        FALSE,
      ],
      'loaded_vendor_interface_method' => [
        '<?php class Test implements \PHP_CodeSniffer\Sniffs\Sniff { public function process(\PHP_CodeSniffer\Files\File $phpcsFile, $stackPtr) {} }',
        'phpcsFile',
        TRUE,
      ],
      'extending_class_variable_in_body_matches_param' => [
        '<?php class Test extends BaseClass { public function test($parameter) { $parameter = 1; } }',
        'parameter',
        TRUE,
        2,
      ],
      'implementing_class_variable_in_body_matches_param' => [
        '<?php class Test implements TestInterface { public function test($parameter) { $parameter = 1; } }',
        'parameter',
        TRUE,
        2,
      ],
      'extending_class_variable_in_body_not_param' => [
        '<?php class Test extends BaseClass { public function test($parameter) { $other_var = 1; } }',
        'other_var',
        FALSE,
      ],
      'same_file_parent_variable_in_body_matches_param' => [
        '<?php class Test extends Base { public function test($parameter) { $parameter = 1; } } class Base { public function test($parameter) {} }',
        'parameter',
        TRUE,
        2,
      ],
      'same_file_parent_variable_in_body_matches_renamed_param' => [
        '<?php class Test extends Base { public function test($renamed) { $renamed = 1; } } class Base { public function test($parameter) {} }',
        'renamed',
        FALSE,
        2,
      ],
      'extending_class_param_imported_into_closure' => [
        '<?php class Test extends BaseClass { public function test($parameter) { return function () use ($parameter) { return $parameter; }; } }',
        'parameter',
        TRUE,
        3,
      ],
      'extending_class_param_captured_by_arrow_function' => [
        '<?php class Test extends BaseClass { public function test($parameter) { return fn() => $parameter; } }',
        'parameter',
        TRUE,
        2,
      ],
      'extending_class_param_not_imported_into_closure' => [
        '<?php class Test extends BaseClass { public function test($parameter) { return function () { return $parameter; }; } }',
        'parameter',
        FALSE,
        2,
      ],
      'extending_class_closure_param_shadowing_method_param' => [
        '<?php class Test extends BaseClass { public function test($parameter) { return function ($parameter) { return $parameter; }; } }',
        'parameter',
        FALSE,
        3,
      ],
      'extending_class_arrow_function_param' => [
        '<?php class Test extends BaseClass { public function test() { return fn($parameter) => $parameter; } }',
        'parameter',
        FALSE,
      ],
    ];
  }

  /**
   * Test isInheritedParameter method with the drupalRoot property.
   *
   * The class extends a Drupal module class that only the Drupal namespace
   * map locates.
   *
   * @param mixed $drupal_root
   *   The drupalRoot property value.
   * @param string $variable_name
   *   Variable name to check.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('dataProviderIsInheritedParameterDrupalRoot')]
  public function testIsInheritedParameterDrupalRoot(mixed $drupal_root, string $variable_name, bool $expected): void {
    $file = $this->processCode('<?php namespace Drupal\my_module; use Drupal\views\Plugin\views\field\FieldPluginBase; class Test extends FieldPluginBase { public function render($values, $extraParam) {} public function formatLabel($labelText) {} }');
    $variable_ptr = $this->findVariableToken($file, $variable_name);
    $sniff = new ParameterNamingSniff();
    $sniff->drupalRoot = $drupal_root;
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isInheritedParameter');
    $result = $method->invoke($sniff, $file, $variable_ptr);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for isInheritedParameter tests with the drupalRoot property.
   *
   * Relative paths resolve from the project root, which is the working
   * directory of the test run.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderIsInheritedParameterDrupalRoot(): array {
    $drupal_root = dirname(__DIR__) . '/Fixtures/Drupal';

    return [
      'absolute_root_upstream_parameter' => [$drupal_root, 'values', TRUE],
      'absolute_root_added_parameter' => [$drupal_root, 'extraParam', FALSE],
      'absolute_root_own_method' => [$drupal_root, 'labelText', FALSE],
      'relative_root_own_method' => ['tests/Fixtures/Drupal', 'labelText', FALSE],
      'false_own_method' => [FALSE, 'labelText', TRUE],
      'not_set_own_method' => [NULL, 'labelText', TRUE],
    ];
  }

  /**
   * Test that isInheritedParameter() throws exception for an invalid root.
   */
  public function testIsInheritedParameterThrowsExceptionForInvalidDrupalRoot(): void {
    $file = $this->processCode('<?php class Test { public function test($parameter) {} }');
    $variable_ptr = $this->findVariableToken($file, 'parameter');
    $sniff = new ParameterNamingSniff();
    $sniff->drupalRoot = 'tests/Fixtures';
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isInheritedParameter');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid drupalRoot "tests/Fixtures": core/lib/Drupal.php not found.');
    $method->invoke($sniff, $file, $variable_ptr);
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
    $sniff = new LocalVariableNamingSniff();
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

  /**
   * Test camelCase detection.
   *
   * @param string $name
   *   The variable name to test.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('dataProviderCamelCaseDetection')]
  public function testCamelCaseDetection(string $name, bool $expected): void {
    $sniff = new LocalVariableNamingSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isCamelCase');

    $result = $method->invoke($sniff, $name);
    $this->assertSame($expected, $result, 'Failed for: ' . $name);
  }

  /**
   * Data provider for camelCase detection tests.
   *
   * @return array<string, array<string|bool>>
   *   Test cases.
   */
  public static function dataProviderCamelCaseDetection(): array {
    return [
      'valid_single_word' => ['test', TRUE],
      'valid_camelCase' => ['testVariable', TRUE],
      'valid_with_number' => ['test123', TRUE],
      'valid_camelCase_with_number' => ['testVariable123', TRUE],
      'valid_long_camelCase' => ['testLongVariableName', TRUE],
      'invalid_snake_case' => ['test_variable', FALSE],
      'invalid_PascalCase' => ['TestVariable', FALSE],
      'invalid_uppercase' => ['TEST', FALSE],
      'invalid_starting_uppercase' => ['Test', FALSE],
      'invalid_with_underscore' => ['test_var', FALSE],
      'invalid_leading_underscore' => ['_test', FALSE],
    ];
  }

  /**
   * Test camelCase conversion.
   *
   * @param string $input
   *   The input name.
   * @param string $expected
   *   Expected output.
   */
  #[DataProvider('providerToCamelCase')]
  public function testToCamelCase(string $input, string $expected): void {
    $sniff = new LocalVariableNamingSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('toCamelCase');

    $result = $method->invoke($sniff, $input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for toCamelCase conversion tests.
   *
   * @return array<string, array<string>>
   *   Test cases.
   */
  public static function providerToCamelCase(): array {
    return [
      'snake_case' => ['test_variable', 'testVariable'],
      'PascalCase' => ['TestVariable', 'testvariable'],
      'already_camel' => ['testVariable', 'testvariable'],
      'with_numbers' => ['test_123_variable', 'test123Variable'],
      'multiple_underscores' => ['test__variable', 'testVariable'],
      'leading_underscore' => ['_test_variable', 'testVariable'],
      'single_word' => ['test', 'test'],
    ];
  }

  /**
   * Test isValidFormat() method.
   *
   * @param string $format
   *   The format to configure.
   * @param string $name
   *   The variable name to test.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('providerIsValidFormat')]
  public function testIsValidFormat(string $format, string $name, bool $expected): void {
    $sniff = new LocalVariableNamingSniff();
    $sniff->format = $format;
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isValidFormat');

    $result = $method->invoke($sniff, $name);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for isValidFormat tests.
   *
   * @return array<string, array<string|bool>>
   *   Test cases.
   */
  public static function providerIsValidFormat(): array {
    return [
      'snakeCase_valid_snake' => ['snakeCase', 'test_variable', TRUE],
      'snakeCase_invalid_camel' => ['snakeCase', 'testVariable', FALSE],
      'camelCase_valid_camel' => ['camelCase', 'testVariable', TRUE],
      'camelCase_invalid_snake' => ['camelCase', 'test_variable', FALSE],
      'snakeCase_single_word' => ['snakeCase', 'test', TRUE],
      'camelCase_single_word' => ['camelCase', 'test', TRUE],
    ];
  }

  /**
   * Test toFormat() method.
   *
   * @param string $format
   *   The format to configure.
   * @param string $input
   *   The input name.
   * @param string $expected
   *   Expected output.
   */
  #[DataProvider('providerToFormat')]
  public function testToFormat(string $format, string $input, string $expected): void {
    $sniff = new LocalVariableNamingSniff();
    $sniff->format = $format;
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('toFormat');

    $result = $method->invoke($sniff, $input);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for toFormat tests.
   *
   * @return array<string, array<string>>
   *   Test cases.
   */
  public static function providerToFormat(): array {
    return [
      'snakeCase_from_camel' => ['snakeCase', 'testVariable', 'test_variable'],
      'snakeCase_from_snake' => ['snakeCase', 'test_variable', 'test_variable'],
      'camelCase_from_snake' => ['camelCase', 'test_variable', 'testVariable'],
      'camelCase_from_camel' => ['camelCase', 'testVariable', 'testvariable'],
    ];
  }

  /**
   * Test that isValidFormat() throws exception for invalid format.
   */
  public function testIsValidFormatThrowsExceptionForInvalidFormat(): void {
    $sniff = new LocalVariableNamingSniff();
    $sniff->format = 'invalidFormat';
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isValidFormat');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid format: invalidFormat');
    $method->invoke($sniff, 'test');
  }

  /**
   * Test that toFormat() throws exception for invalid format.
   */
  public function testToFormatThrowsExceptionForInvalidFormat(): void {
    $sniff = new LocalVariableNamingSniff();
    $sniff->format = 'invalidFormat';
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('toFormat');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Invalid format: invalidFormat');
    $method->invoke($sniff, 'test');
  }

  /**
   * Test leading underscore detection.
   *
   * @param string $name
   *   The variable name to test.
   * @param bool $expected
   *   Expected result.
   */
  #[DataProvider('providerHasLeadingUnderscore')]
  public function testHasLeadingUnderscore(string $name, bool $expected): void {
    $sniff = new LocalVariableNamingSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('hasLeadingUnderscore');

    $result = $method->invoke($sniff, $name);
    $this->assertSame($expected, $result, 'Failed for: ' . $name);
  }

  /**
   * Data provider for hasLeadingUnderscore tests.
   *
   * @return array<string, array<string|bool>>
   *   Test cases.
   */
  public static function providerHasLeadingUnderscore(): array {
    return [
      'leading_underscore_snake' => ['_static_value', TRUE],
      'leading_underscore_camel' => ['_staticValue', TRUE],
      'leading_underscore_only' => ['_', TRUE],
      'leading_double_underscore' => ['__internal', TRUE],
      'no_leading_underscore_snake' => ['static_value', FALSE],
      'no_leading_underscore_camel' => ['staticValue', FALSE],
      'underscore_in_middle' => ['static_value', FALSE],
      'trailing_underscore' => ['value_', FALSE],
      'single_letter' => ['a', FALSE],
    ];
  }

  /**
   * Asserts the function token that a lookup method returns for a variable.
   *
   * @param string $method_name
   *   The lookup method to call.
   * @param string $code
   *   PHP code to test.
   * @param string $variable_name
   *   Variable name to look up.
   * @param int $occurrence
   *   Which occurrence of the variable to look up, starting at 1.
   * @param array{0: int|string, 1: int}|null $expected
   *   The code and occurrence of the expected function token, or NULL when the
   *   lookup is expected to return FALSE.
   */
  protected function assertFunctionLookup(string $method_name, string $code, string $variable_name, int $occurrence, ?array $expected): void {
    $file = $this->processCode($code);
    $variable_ptr = $this->findVariableToken($file, $variable_name, $occurrence);
    $expected_ptr = $expected === NULL ? FALSE : $this->findTokenByCode($file, $expected[0], $expected[1]);
    $sniff = new LocalVariableNamingSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod($method_name);
    $result = $method->invoke($sniff, $file, $variable_ptr);

    $this->assertSame($expected_ptr, $result);
  }

}
