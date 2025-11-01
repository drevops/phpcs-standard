<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\NamingConventions\VariableSnakeCaseSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for VariableSnakeCaseSniff::isProperty() method.
 */
#[CoversClass(VariableSnakeCaseSniff::class)]
class VariableSnakeCaseSniffIsPropertyTest extends UnitTestCase {

  #[DataProvider('providerIsProperty')]
  public function testIsProperty(string $code, string $variable_name, bool $expected): void {
    $file = $this->processCode($code);
    $variable_ptr = $this->findVariableToken($file, $variable_name);
    $sniff = new VariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('isProperty');
    $result = $method->invoke($sniff, $file, $variable_ptr);
    $this->assertSame($expected, $result);
  }

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
    ];
  }

}
