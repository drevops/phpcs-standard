<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\KernelTests\KernelTestBase;

// KernelTestBase lives in core/tests, outside Drupal extensions, so it is not
// resolved.
abstract class ViewsKernelTestBase extends KernelTestBase {

  protected function setUp($import_test_views = TRUE): void {
  }

}
