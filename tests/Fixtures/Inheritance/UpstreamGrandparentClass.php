<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures\Inheritance;

class UpstreamGrandparentClass implements \Countable {

  public function grandparentMethod(string $grandparentParam): void {
  }

  public function count(): int {
    return 0;
  }

}
