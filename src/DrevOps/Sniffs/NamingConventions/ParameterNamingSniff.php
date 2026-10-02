<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;

/**
 * Enforces consistent naming convention for function/method parameters.
 *
 * This sniff checks that function and method parameters use the configured
 * naming format (snakeCase or camelCase). Local variables and class properties
 * are excluded. A parameter is also excluded when an ancestor class, interface
 * or trait declares the same method with a parameter of the same name.
 */
final class ParameterNamingSniff extends AbstractVariableNamingSniff {

  /**
   * Error code for non-snake_case parameters.
   */
  public const string CODE_PARAMETER_NOT_SNAKE_CASE = 'NotSnakeCase';

  /**
   * Error code for non-camelCase parameters.
   */
  public const string CODE_PARAMETER_NOT_CAMEL_CASE = 'NotCamelCase';

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

    // Only process parameters (declaration only, not usage in body).
    // Local variables handled by LocalVariableNamingSniff.
    if (!$this->isParameter($phpcsFile, $stackPtr, FALSE)) {
      return;
    }

    // Keep names that an ancestor declares for the same method, so named
    // arguments written against the ancestor keep working.
    if ($this->isInheritedParameter($phpcsFile, $stackPtr)) {
      return;
    }

    // Check if the variable name follows the configured format.
    if (!$this->isValidFormat($var_name)) {
      $suggestion = $this->toFormat($var_name);
      $error = 'Variable "$%s" is not in %s format; try "$%s"';
      $data = [$var_name, $this->format, $suggestion];

      // Determine the error code based on the configured format.
      $error_code = ($this->format === 'snakeCase') ?
        self::CODE_PARAMETER_NOT_SNAKE_CASE :
        self::CODE_PARAMETER_NOT_CAMEL_CASE;

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

        // Also fix the parameter name in the docblock @param tag if present.
        $this->fixDocblockParam($phpcsFile, $stackPtr, $var_name, $suggestion);
      }
      // @codeCoverageIgnoreEnd
    }
  }

  /**
   * Find the docblock for a function/method.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $function_ptr
   *   The position of the function token.
   *
   * @return int|false
   *   The position of the docblock open tag, or FALSE if not found.
   */
  private function findFunctionDocblock(File $phpcs_file, int $function_ptr): int|false {
    $tokens = $phpcs_file->getTokens();

    // Search backwards for docblock, skipping whitespace, comments, attributes,
    // visibility modifiers, and other function modifiers.
    $skip_tokens = [
      T_WHITESPACE,
      T_COMMENT,
      T_ATTRIBUTE,
      T_ATTRIBUTE_END,
      T_PUBLIC,
      T_PROTECTED,
      T_PRIVATE,
      T_STATIC,
      T_FINAL,
      T_ABSTRACT,
    ];

    $search = $phpcs_file->findPrevious($skip_tokens, $function_ptr - 1, NULL, TRUE);

    if ($search !== FALSE && $tokens[$search]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
      // Found a docblock close tag, return its opener.
      return $tokens[$search]['comment_opener'] ?? FALSE;
    }

    return FALSE;
  }

  /**
   * Fix the parameter name in the docblock @param tag.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $stack_ptr
   *   The position of the parameter variable token.
   * @param string $old_name
   *   The old parameter name (without $).
   * @param string $new_name
   *   The new parameter name (without $).
   *
   * @codeCoverageIgnore
   */
  private function fixDocblockParam(File $phpcs_file, int $stack_ptr, string $old_name, string $new_name): void {
    $tokens = $phpcs_file->getTokens();

    // Find the enclosing function.
    $function_ptr = $this->findEnclosingFunction($phpcs_file, $stack_ptr);
    if ($function_ptr === FALSE) {
      return;
    }

    // Find the function's docblock.
    $docblock_start = $this->findFunctionDocblock($phpcs_file, $function_ptr);
    if ($docblock_start === FALSE) {
      return;
    }

    $docblock_end = $tokens[$docblock_start]['comment_closer'] ?? FALSE;
    if ($docblock_end === FALSE) {
      return;
    }

    // Search for @param tags in the docblock.
    for ($i = $docblock_start; $i < $docblock_end; $i++) {
      if ($tokens[$i]['code'] === T_DOC_COMMENT_TAG && $tokens[$i]['content'] === '@param') {
        // Found a @param tag. Look for the parameter name in the following
        // tokens.
        // The format is typically: @param type $paramName description.
        for ($j = $i + 1; $j < $docblock_end; $j++) {
          $token = $tokens[$j];

          // Check for the parameter variable name.
          if ($token['code'] === T_DOC_COMMENT_STRING) {
            $content = $token['content'];

            // Check if this string contains the parameter name.
            // Parameter names in docblocks are prefixed with $.
            if (preg_match('/\$' . preg_quote($old_name, '/') . '(?![a-zA-Z0-9_])/', $content) === 1) {
              // Replace the parameter name in the string.
              $new_content = preg_replace(
                '/\$' . preg_quote($old_name, '/') . '(?![a-zA-Z0-9_])/',
                '$' . $new_name,
                $content
              );
              $phpcs_file->fixer->replaceToken($j, $new_content);
              // Stop searching after finding the parameter for this @param tag.
              break;
            }
          }

          // Stop if we hit another @tag (moved to next tag).
          if ($token['code'] === T_DOC_COMMENT_TAG) {
            break;
          }
        }
      }
    }
  }

}
