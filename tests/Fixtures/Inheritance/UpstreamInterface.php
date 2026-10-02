<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures\Inheritance;

interface UpstreamInterface extends UpstreamParentInterface {

  public function upstreamMethod(string $upstreamParam): void;

}
