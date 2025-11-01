<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\NamingConventions\VariableSnakeCaseSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for VariableSnakeCaseSniff.
 */
#[CoversClass(VariableSnakeCaseSniff::class)]
class VariableSnakeCaseSniffTest extends UnitTestCase {

  /**
   * Test that the sniff registers the correct token types.
   */
  public function testRegister(): void {
    $sniff = new VariableSnakeCaseSniff();
    $tokens = $sniff->register();

    $this->assertContains(T_VARIABLE, $tokens);
  }

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
    $sniff = new VariableSnakeCaseSniff();
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
    $sniff = new VariableSnakeCaseSniff();
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
    $sniff = new VariableSnakeCaseSniff();
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

  public function testErrorCodeConstant(): void {
    $this->assertSame('VariableNotSnakeCase', VariableSnakeCaseSniff::CODE_VARIABLE_NOT_SNAKE_CASE);
  }

}
