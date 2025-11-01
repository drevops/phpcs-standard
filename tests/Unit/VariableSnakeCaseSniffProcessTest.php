<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\NamingConventions\VariableSnakeCaseSniff;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for VariableSnakeCaseSniff::process() method.
 */
#[CoversClass(VariableSnakeCaseSniff::class)]
class VariableSnakeCaseSniffProcessTest extends UnitTestCase {

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

  public static function providerProcessCases(): array {
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
        TRUE,
      ],
    ];
  }

}
