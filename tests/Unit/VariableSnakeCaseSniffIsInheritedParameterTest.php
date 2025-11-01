<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\NamingConventions\VariableSnakeCaseSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for VariableSnakeCaseSniff::isInheritedParameter() method.
 */
#[CoversClass(VariableSnakeCaseSniff::class)]
class VariableSnakeCaseSniffIsInheritedParameterTest extends UnitTestCase {

  #[DataProvider('providerIsInheritedParameter')]
  public function testIsInheritedParameter(string $code, string $variable_name, bool $expected): void {
    $file = $this->processCode($code);
    $variable_ptr = $this->findVariableToken($file, $variable_name);
    $sniff = new VariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isInheritedParameter');
    $result = $method->invoke($sniff, $file, $variable_ptr);
    $this->assertSame($expected, $result);
  }

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

}
