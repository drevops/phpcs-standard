<?php

declare(strict_types=1);

namespace Drupal\views\Plugin\views\field;

use Drupal\views\ResultRow;

abstract class FieldPluginBase {

  public function render(ResultRow $values) {
  }

}
