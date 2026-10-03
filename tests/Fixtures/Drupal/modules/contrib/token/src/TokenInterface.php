<?php

declare(strict_types=1);

namespace Drupal\token;

interface TokenInterface {

  public function replace(string $text, array $tokenData = []);

}
