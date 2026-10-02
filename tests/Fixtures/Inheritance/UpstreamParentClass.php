<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures\Inheritance;

abstract class UpstreamParentClass extends UpstreamGrandparentClass {

  public function __construct(array $constructorParam) {
  }

  public function parentMethod(string $parentParam): void {
  }

  protected function protectedParentMethod(string $protectedParam): void {
  }

  private function privateParentMethod(string $privateParam): void {
  }

  abstract public function abstractParentMethod(string $abstractParam): void;

  public function CaseInsensitiveMethod(string $caseParam): void {
  }

}
