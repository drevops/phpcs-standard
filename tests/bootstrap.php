<?php

/**
 * @file
 * PHPUnit bootstrap file.
 *
 * Loads composer autoloader and PHP_CodeSniffer autoloader.
 */

declare(strict_types=1);

// Load Composer autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

// Define PHPCS constants that are normally set in the phpcs/phpcbf scripts.
if (!defined('PHP_CODESNIFFER_CBF')) {
  define('PHP_CODESNIFFER_CBF', FALSE);
}

if (!defined('PHP_CODESNIFFER_VERBOSITY')) {
  define('PHP_CODESNIFFER_VERBOSITY', 0);
}

if (!defined('PHP_CODESNIFFER_IN_TESTS')) {
  define('PHP_CODESNIFFER_IN_TESTS', TRUE);
}

// Load PHP_CodeSniffer autoloader.
require_once __DIR__ . '/../vendor/squizlabs/php_codesniffer/autoload.php';

// Load PHPCS token definitions.
require_once __DIR__ . '/../vendor/squizlabs/php_codesniffer/src/Util/Tokens.php';
