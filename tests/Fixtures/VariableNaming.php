<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

class ClassWithMixedVariableNaming {

  public $propertyIgnoredCamelCase;

  public $property_ignored_snake_case;

  public static $static_property_ignored;

  public static $camelCaseStaticProperty;

  public function methodWithMixedLocalVariables(): void {
    $valid_snake_case_local = 'valid';
    $another_valid_local = 123;
    $valid_with_numbers_123 = 'valid';

    $invalidVariable = 'invalid';
    $anotherInvalid = 123;
    $testCamelCase = 'invalid';

    // Instance property accesses should be ignored.
    $this->propertyIgnoredCamelCase = 'value';
    $this->anotherCamelCaseProperty = 'value';
    $value = $this->propertyIgnoredCamelCase;

    $_SERVER['test'] = 'value';
    $_GET['key'] = 'value';

    // Static property accesses should be ignored.
    self::$static_property_ignored = 'value';
    self::$camelCaseStaticProperty = 'value';
    static::$static_property_ignored = 'value';
    ClassWithMixedVariableNaming::$camelCaseStaticProperty = 'value';

    if (isset($_POST['data'])) {
      $valid_data = $_POST['data'];
    }
  }

  public function methodWithMixedParams(string $valid_param, int $invalidParam): void {
    $result = $valid_param . $invalidParam;
  }

  public function methodWithClosure(): void {
    $valid_local = 'valid';

    $closure = function ($valid_param, $invalidParam) {
      $valid_local_var = 'valid';

      $invalidVar = 'invalid';

      return $valid_param . $invalidParam;
    };
  }

}

class ClassWithPromotedProperties {

  public function __construct(
    public string $promotedPropertyOne,
    public string $promoted_property_two,
  ) {
    $localVar = 'invalid';
    $valid_local = 'valid';
  }

}

function functionWithMixedParams(string $valid_param, int $invalidParam): void {
  $valid_var = 'valid';

  $invalidVar = 'invalid';
}
