<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;

/**
 * Enforces snake_case naming for local variables.
 *
 * This sniff checks that local variables use snake_case format.
 * Function/method parameters and class properties are excluded.
 */
final class LocalVariableSnakeCaseSniff extends AbstractSnakeCaseSniff {

  /**
   * Error code for non-snake_case variables.
   */
  public const string CODE_VARIABLE_NOT_SNAKE_CASE = 'NotSnakeCase';

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

    // Skip class/trait properties - we only check local variables.
    if ($this->isProperty($phpcsFile, $stackPtr)) {
      return;
    }

    // Skip parameters (both declaration and usage).
    // Handled by ParameterSnakeCaseSniff.
    if ($this->isParameter($phpcsFile, $stackPtr, TRUE)) {
      return;
    }

    // Check if the variable name is in snake_case format.
    if (!$this->isSnakeCase($var_name)) {
      $suggestion = $this->toSnakeCase($var_name);
      $error = 'Variable "$%s" is not in snake_case format; try "$%s"';
      $data = [$var_name, $suggestion];

      $fix = $phpcsFile->addFixableError(
        $error,
        $stackPtr,
        self::CODE_VARIABLE_NOT_SNAKE_CASE,
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
