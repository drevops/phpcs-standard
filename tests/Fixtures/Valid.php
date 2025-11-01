<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

function functionWithValidSnakeCaseParams($valid_param_one, $valid_param_two) {
  $valid_local_variable = 100;
  $another_valid_local = $valid_local_variable * $valid_param_two;
  return $valid_local_variable + $another_valid_local;
}

class ClassWithValidSnakeCaseNaming {

  public $propertyWithAnyNaming;

  public function methodWithValidSnakeCaseParam($valid_param) {
    $valid_local_variable = strtolower($valid_param);
    return $valid_local_variable;
  }

}
