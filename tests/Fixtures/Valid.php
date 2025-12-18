<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

function functionWithValidSnakeCaseParams($valid_param_one, $valid_param_two) {
  $valid_local_variable = 100;
  $another_valid_local = $valid_local_variable * $valid_param_two;
  // Variables with leading underscores should be skipped.
  $_static_value = 50;
  $_internalVar = 25;
  return $valid_local_variable + $another_valid_local + $_static_value + $_internalVar;
}

/**
 * Function with underscore-prefixed parameter (should be skipped).
 */
function functionWithUnderscorePrefixedParam($_prefixed_param) {
  return $_prefixed_param * 2;
}

class ClassWithValidSnakeCaseNaming {

  public $propertyWithAnyNaming;

  public function methodWithValidSnakeCaseParam($valid_param) {
    $valid_local_variable = strtolower($valid_param);
    // Variables with leading underscores should be skipped.
    $_internal_cache = [];
    $_tempValue = NULL;
    return $valid_local_variable;
  }

  /**
   * Method with underscore-prefixed parameter (should be skipped).
   */
  public function methodWithUnderscorePrefixedParam($_prefixed_param) {
    return $_prefixed_param;
  }

}
