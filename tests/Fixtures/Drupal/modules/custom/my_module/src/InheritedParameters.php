<?php

declare(strict_types=1);

namespace Drupal\my_module;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\token\TokenInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

// Views field plugin extending a core module class.
class MyField extends FieldPluginBase {

  // FieldPluginBase declares render(ResultRow $values): checked.
  public function render(ResultRow $resultRow) {
  }

  // Not declared by any ancestor: checked.
  public function formatLabel(string $labelText) {
  }

}

// Service implementing a contrib module interface.
class MyTokenReplacer implements TokenInterface {

  // Keeps the interface name (exempt) and adds one (checked).
  public function replace(string $text, array $tokenData = [], array $replaceOptions = []) {
  }

}

// Kernel test base extending a core module test class.
abstract class MyViewsKernelTestBase extends ViewsKernelTestBase {

  // ViewsKernelTestBase declares setUp($import_test_views): checked.
  protected function setUp($importTestViews = TRUE): void {
  }

  // KernelTestBase is not resolved and may declare this method: exempt.
  protected function createTestNode(array $nodeValues): void {
  }

}

// Class without ancestors: checked with and without discovery.
class MyFormatter {

  public function format(string $rawValue): void {
  }

}
