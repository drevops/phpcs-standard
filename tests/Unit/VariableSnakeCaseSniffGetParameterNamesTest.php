<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\NamingConventions\VariableSnakeCaseSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for VariableSnakeCaseSniff::getParameterNames() method.
 */
#[CoversClass(VariableSnakeCaseSniff::class)]
class VariableSnakeCaseSniffGetParameterNamesTest extends UnitTestCase {

  #[DataProvider('providerGetParameterNames')]
  public function testGetParameterNames(string $code, array $expected_params): void {
    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);
    $sniff = new VariableSnakeCaseSniff();
    $reflection = new \ReflectionClass($sniff);
    $method = $reflection->getMethod('getParameterNames');
    $result = $method->invoke($sniff, $file, $function_ptr);

    $this->assertIsArray($result);

    $this->assertCount(count($expected_params), $result);
    foreach ($expected_params as $param) {
      $this->assertContains($param, $result);
    }
  }

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

}
