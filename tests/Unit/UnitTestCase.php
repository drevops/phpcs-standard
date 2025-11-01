<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\LocalFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;

/**
 * Abstract base class for unit tests.
 *
 * Provides helper methods for processing PHP code with PHPCS and finding
 * tokens in the token stream.
 */
abstract class UnitTestCase extends TestCase {

  /**
   * The PHPCS configuration.
   */
  protected Config $config;

  /**
   * The PHPCS ruleset.
   */
  protected Ruleset $ruleset;

  protected function setUp(): void {
    parent::setUp();
    $this->config = new Config();
    $this->config->standards = ['DrevOps'];
    $this->ruleset = new Ruleset($this->config);
  }

  /**
   * Process PHP code with PHPCS and return the file object.
   *
   * @param string $code
   *   The PHP code to process.
   *
   * @return \PHP_CodeSniffer\Files\LocalFile
   *   The processed file object.
   */
  protected function processCode(string $code): LocalFile {
    $temp_file = tempnam(sys_get_temp_dir(), 'phpcs_test_');
    file_put_contents($temp_file, $code);
    $file = new LocalFile($temp_file, $this->ruleset, $this->config);
    $file->process();
    unlink($temp_file);
    return $file;
  }

  /**
   * Find a variable token in the token stream by name.
   *
   * @param \PHP_CodeSniffer\Files\LocalFile $file
   *   The file object.
   * @param string $variable_name
   *   The variable name (without $).
   *
   * @return int
   *   The token pointer position.
   */
  protected function findVariableToken(LocalFile $file, string $variable_name): int {
    $tokens = $file->getTokens();
    foreach ($tokens as $ptr => $token) {
      if ($token['code'] === T_VARIABLE && ltrim($token['content'], '$') === $variable_name) {
        return $ptr;
      }
    }
    $this->fail(sprintf('Variable $%s not found in token stream', $variable_name));
  }

  /**
   * Find a function token in the token stream.
   *
   * @param \PHP_CodeSniffer\Files\LocalFile $file
   *   The file object.
   *
   * @return int
   *   The token pointer position.
   */
  protected function findFunctionToken(LocalFile $file): int {
    $tokens = $file->getTokens();
    foreach ($tokens as $ptr => $token) {
      if ($token['code'] === T_FUNCTION) {
        return $ptr;
      }
    }
    $this->fail('Function token not found in token stream');
  }

  /**
   * Find a class token in the token stream.
   *
   * @param \PHP_CodeSniffer\Files\LocalFile $file
   *   The file object.
   *
   * @return int
   *   The token pointer position.
   */
  protected function findClassToken(LocalFile $file): int {
    $tokens = $file->getTokens();
    foreach ($tokens as $ptr => $token) {
      if ($token['code'] === T_CLASS) {
        return $ptr;
      }
    }
    $this->fail('Class token not found in token stream');
  }

  /**
   * Find an interface token in the token stream.
   *
   * @param \PHP_CodeSniffer\Files\LocalFile $file
   *   The file object.
   *
   * @return int
   *   The token pointer position.
   */
  protected function findInterfaceToken(LocalFile $file): int {
    $tokens = $file->getTokens();
    foreach ($tokens as $ptr => $token) {
      if ($token['code'] === T_INTERFACE) {
        return $ptr;
      }
    }
    $this->fail('Interface token not found in token stream');
  }

  /**
   * Find a trait token in the token stream.
   *
   * @param \PHP_CodeSniffer\Files\LocalFile $file
   *   The file object.
   *
   * @return int
   *   The token pointer position.
   */
  protected function findTraitToken(LocalFile $file): int {
    $tokens = $file->getTokens();
    foreach ($tokens as $ptr => $token) {
      if ($token['code'] === T_TRAIT) {
        return $ptr;
      }
    }
    $this->fail('Trait token not found in token stream');
  }

}
