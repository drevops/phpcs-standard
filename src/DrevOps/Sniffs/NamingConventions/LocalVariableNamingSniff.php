<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;

/**
 * Enforces consistent naming convention for local variables.
 *
 * This sniff checks that local variables use the configured naming format
 * (snakeCase or camelCase). Function/method parameters and class properties
 * are excluded.
 */
final class LocalVariableNamingSniff extends AbstractVariableNamingSniff {

  /**
   * Error code for non-snake_case variables.
   */
  public const CODE_VARIABLE_NOT_SNAKE_CASE = 'NotSnakeCase';

  /**
   * Error code for non-camelCase variables.
   */
  public const CODE_VARIABLE_NOT_CAMEL_CASE = 'NotCamelCase';

  /**
   * {@inheritdoc}
   */
  public function process(File $phpcsFile, $stackPtr): void {
    $tokens = $phpcsFile->getTokens();
    $var_name = ltrim($tokens[$stackPtr]['content'] ?? '', '$');

    // Skip reserved variables (superglobals, $this, etc.).
    if ($this->isReserved($var_name)) {
      return;
    }

    // Skip variables with leading underscores (e.g., $_static_value).
    if ($this->hasLeadingUnderscore($var_name)) {
      return;
    }

    // Skip static property accesses (self::$prop, static::$prop, etc.).
    if ($this->isStaticPropertyAccess($phpcsFile, $stackPtr)) {
      return;
    }

    // Skip class/trait properties - we only check local variables.
    if ($this->isProperty($phpcsFile, $stackPtr)) {
      return;
    }

    // Skip parameters (both declaration and usage).
    // Handled by ParameterNamingSniff.
    if ($this->isParameter($phpcsFile, $stackPtr, TRUE)) {
      return;
    }

    // Check if the variable name follows the configured format.
    if (!$this->isValidFormat($var_name)) {
      $suggestion = $this->toFormat($var_name);
      $error = 'Variable "$%s" is not in %s format; try "$%s"';
      $data = [$var_name, $this->format, $suggestion];

      // Determine the error code based on the configured format.
      $error_code = ($this->format === 'snakeCase') ?
        self::CODE_VARIABLE_NOT_SNAKE_CASE :
        self::CODE_VARIABLE_NOT_CAMEL_CASE;

      $fix = $phpcsFile->addFixableError(
        $error,
        $stackPtr,
        $error_code,
        $data
      );

      // @codeCoverageIgnoreStart
      // Auto-fix code only executes when running phpcbf (PHP Code Beautifier
      // and Fixer). Unit tests only check for error detection, not fixing.
      if ($fix === TRUE) {
        $phpcsFile->fixer->replaceToken($stackPtr, '$' . $suggestion);
      }
      // @codeCoverageIgnoreEnd
    }
  }

}
