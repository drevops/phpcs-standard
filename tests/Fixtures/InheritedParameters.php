<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

interface InterfaceDefiningInheritedParams {

  // Declared here first: checked.
  public function methodWithInheritedParams(string $interfaceParamOne, int $interfaceParamTwo): bool;

}

interface InterfaceExtendingInterface extends InterfaceDefiningInheritedParams {

  // Redeclared from the parent interface: exempt.
  public function methodWithInheritedParams(string $interfaceParamOne, int $interfaceParamTwo): bool;

  // Declared here first: checked.
  public function methodDeclaredByChildInterface(string $childInterfaceParam): void;

}

abstract class AbstractClassDefiningInheritedParam {

  // Declared here first: checked.
  abstract public function methodWithInheritedParam(array $abstractParam): void;

  // Follows an abstract method but is not inherited: checked.
  public function concreteMethodAfterAbstract(array $concreteParam): void {
  }

}

class ClassImplementingInterface implements InterfaceDefiningInheritedParams {

  // Implements the interface method with the same names: exempt.
  public function methodWithInheritedParams(string $interfaceParamOne, int $interfaceParamTwo): bool {
    $valid_snake_case = 'valid';
    $result = $interfaceParamOne . $interfaceParamTwo;
    $localInvalidCamelCase = 'error';

    return TRUE;
  }

  // Not declared by the interface: checked.
  public function methodNotInInterface(string $ownParam): void {
  }

}

class ClassExtendingAbstractClass extends AbstractClassDefiningInheritedParam {

  // Keeps the inherited name (exempt) and adds a parameter (checked).
  public function methodWithInheritedParam(array $abstractParam, ?string $extraParam = NULL): void {
    $valid_snake_case = $abstractParam['key'];
    $another_valid = $extraParam;
    $localInvalidCamelCase = 'error';
  }

}

class ClassRenamingInheritedParam extends AbstractClassDefiningInheritedParam {

  // Renames the inherited parameter: checked.
  public function methodWithInheritedParam(array $renamedParam): void {
  }

  // Private methods never inherit a signature: checked.
  private function privateHelper(string $privateParam): void {
  }

}

class ClassWithNoInheritance {

  public function methodWithNonInheritedParams($invalidNonInheritedParamOne, $invalidNonInheritedParamTwo) {
    $valid_snake_case = 'valid';
    $local_valid = $invalidNonInheritedParamOne . $invalidNonInheritedParamTwo;
  }

}
