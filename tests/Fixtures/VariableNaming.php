<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

class ClassWithMixedVariableNaming {

  public $propertyIgnoredCamelCase;

  public $property_ignored_snake_case;

  public static $static_property_ignored;

  public static $camelCaseStaticProperty;

  protected ?string $nullableProperty = NULL;

  protected ?\DOMDocument $xmlDom = NULL;

  protected ?\DateTime $dateTime = NULL;

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
    public ?\DOMDocument $promotedNullableProperty = NULL,
  ) {
    $localVar = 'invalid';
    $valid_local = 'valid';
  }

}

function functionWithMixedParams(string $valid_param, int $invalidParam): void {
  $valid_var = 'valid';

  $invalidVar = 'invalid';
}

/**
 * Function with variables that have @var comments.
 */
function functionWithVarComments(): void {
  /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
  $moduleHandler = get_service('module_handler');

  /** @var SomeClass $validVar */
  $valid_var = get_service('valid');
}
