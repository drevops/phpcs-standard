<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

interface InterfaceDefiningInheritedParams {

  public function methodWithInheritedParams(string $validInheritedParamOne, int $validInheritedParamTwo): bool;

}

abstract class AbstractClassDefiningInheritedParam {

  abstract public function methodWithInheritedParam(array $validInheritedParam): void;

}

class ClassImplementingInterface implements InterfaceDefiningInheritedParams {

  public function methodWithInheritedParams(string $validInheritedParamOne, int $validInheritedParamTwo): bool {
    $valid_snake_case = 'valid';
    $result = $validInheritedParamOne . $validInheritedParamTwo;
    $localInvalidCamelCase = 'error';

    return TRUE;
  }

}

class ClassExtendingAbstractClass extends AbstractClassDefiningInheritedParam {

  public function methodWithInheritedParam(array $validInheritedParam): void {
    $valid_snake_case = $validInheritedParam['key'];
    $another_valid = $validInheritedParam['value'];
    $localInvalidCamelCase = 'error';
  }

}

class ClassWithNoInheritance {

  public function methodWithNonInheritedParams($invalidNonInheritedParamOne, $invalidNonInheritedParamTwo) {
    $valid_snake_case = 'valid';
    $local_valid = $invalidNonInheritedParamOne . $invalidNonInheritedParamTwo;
  }

}
