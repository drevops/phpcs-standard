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
   * @param int $occurrence
   *   Which occurrence of the variable to find, starting at 1.
   *
   * @return int
   *   The token pointer position.
   */
  protected function findVariableToken(LocalFile $file, string $variable_name, int $occurrence = 1): int {
    $tokens = $file->getTokens();
    foreach ($tokens as $ptr => $token) {
      if ($token['code'] !== T_VARIABLE || ltrim($token['content'], '$') !== $variable_name) {
        continue;
      }

      $occurrence--;

      if ($occurrence === 0) {
        return $ptr;
      }
    }
    $this->fail(sprintf('Variable $%s not found in token stream', $variable_name));
  }

  /**
   * Find a token in the token stream by code.
   *
   * @param \PHP_CodeSniffer\Files\LocalFile $file
   *   The file object.
   * @param int|string $code
   *   The token code.
   * @param int $occurrence
   *   Which occurrence of the token to find, starting at 1.
   *
   * @return int
   *   The token pointer position.
   */
  protected function findTokenByCode(LocalFile $file, int|string $code, int $occurrence = 1): int {
    foreach ($file->getTokens() as $ptr => $token) {
      if ($token['code'] !== $code) {
        continue;
      }

      $occurrence--;

      if ($occurrence === 0) {
        return $ptr;
      }
    }
    $this->fail(sprintf('Token %s not found in token stream', is_int($code) ? token_name($code) : $code));
  }

  /**
   * Find a function token in the token stream by name.
   *
   * @param \PHP_CodeSniffer\Files\LocalFile $file
   *   The file object.
   * @param string $function_name
   *   The function name.
   *
   * @return int
   *   The position of the first function token with the name.
   */
  protected function findFunctionTokenByName(LocalFile $file, string $function_name): int {
    $tokens = $file->getTokens();
    foreach ($tokens as $ptr => $token) {
      if ($token['code'] === T_FUNCTION && $file->getDeclarationName($ptr) === $function_name) {
        return $ptr;
      }
    }
    $this->fail(sprintf('Function %s not found in token stream', $function_name));
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
