<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures\Inheritance;

trait UpstreamTrait {

  use UpstreamNestedTrait;

  abstract public function traitMethod(string $traitParam): void;

}
