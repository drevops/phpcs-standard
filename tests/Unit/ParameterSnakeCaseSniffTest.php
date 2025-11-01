<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use PHP_CodeSniffer\Ruleset;
use DrevOps\Sniffs\NamingConventions\ParameterSnakeCaseSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for ParameterSnakeCaseSniff.
 *
 * Tests sniff-specific logic. Shared base class methods are tested
 * in AbstractVariableSnakeCaseSniffTest.
 */
#[CoversClass(ParameterSnakeCaseSniff::class)]
class ParameterSnakeCaseSniffTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();
    // Configure to run only ParameterSnakeCase sniff.
    $this->config->sniffs = ['DrevOps.NamingConventions.ParameterSnakeCase'];
    $this->ruleset = new Ruleset($this->config);
  }

  /**
   * Test that the sniff registers the correct token types.
   */
  public function testRegister(): void {
    $sniff = new ParameterSnakeCaseSniff();
    $tokens = $sniff->register();

    $this->assertContains(T_VARIABLE, $tokens);
  }

  /**
   * Test error code constant.
   */
  public function testErrorCodeConstant(): void {
    $this->assertSame('NotSnakeCase', ParameterSnakeCaseSniff::CODE_PARAMETER_NOT_SNAKE_CASE);
  }

  /**
   * Test process method validates parameters.
   *
   * @param string $code
   *   PHP code to test.
   * @param bool $should_have_errors
   *   Whether errors should be detected.
   */
  #[DataProvider('providerProcessCases')]
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
  public static function providerProcessCases(): array {
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

}
