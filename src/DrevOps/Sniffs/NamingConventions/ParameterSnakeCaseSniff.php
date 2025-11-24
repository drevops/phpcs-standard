<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;

/**
 * Enforces snake_case naming for function/method parameters.
 *
 * This sniff checks that function and method parameters use snake_case format.
 * Local variables and class properties are excluded.
 * Parameters inherited from interfaces/parent classes are also excluded.
 */
final class ParameterSnakeCaseSniff extends AbstractSnakeCaseSniff {

  /**
   * Error code for non-snake_case parameters.
   */
  public const CODE_PARAMETER_NOT_SNAKE_CASE = 'NotSnakeCase';

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

    // Only process parameters (declaration only, not usage in body).
    // Local variables handled by LocalVariableSnakeCaseSniff.
    if (!$this->isParameter($phpcsFile, $stackPtr, FALSE)) {
      return;
    }

    // Skip parameters from inherited/implemented methods as they can't be
    // changed.
    if ($this->isInheritedParameter($phpcsFile, $stackPtr)) {
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
        self::CODE_PARAMETER_NOT_SNAKE_CASE,
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
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $functionPtr
   *   The position of the function token.
   *
   * @return int|false
   *   The position of the docblock open tag, or FALSE if not found.
   */
  private function findFunctionDocblock(File $phpcsFile, int $functionPtr): int|false {
    $tokens = $phpcsFile->getTokens();

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

    $search = $phpcsFile->findPrevious($skip_tokens, $functionPtr - 1, NULL, TRUE);

    if ($search !== FALSE && $tokens[$search]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
      // Found a docblock close tag, return its opener.
      return $tokens[$search]['comment_opener'] ?? FALSE;
    }

    return FALSE;
  }

  /**
   * Fix the parameter name in the docblock @param tag.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $stackPtr
   *   The position of the parameter variable token.
   * @param string $oldName
   *   The old parameter name (without $).
   * @param string $newName
   *   The new parameter name (without $).
   *
   * @codeCoverageIgnore
   */
  private function fixDocblockParam(File $phpcsFile, int $stackPtr, string $oldName, string $newName): void {
    $tokens = $phpcsFile->getTokens();

    // Find the enclosing function.
    $function_ptr = $this->findEnclosingFunction($phpcsFile, $stackPtr);
    if ($function_ptr === FALSE) {
      return;
    }

    // Find the function's docblock.
    $docblock_start = $this->findFunctionDocblock($phpcsFile, $function_ptr);
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
            if (preg_match('/\$' . preg_quote($oldName, '/') . '(?![a-zA-Z0-9_])/', $content) === 1) {
              // Replace the parameter name in the string.
              $new_content = preg_replace(
                '/\$' . preg_quote($oldName, '/') . '(?![a-zA-Z0-9_])/',
                '$' . $newName,
                $content
              );
              $phpcsFile->fixer->replaceToken($j, $new_content);
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
