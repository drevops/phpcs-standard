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
   * The comment types to fix when auto-fixing variable names.
   *
   * Comma-separated list of comment types:
   * - 'doc': Fix variable references in doc comments (/** ... *\/)
   * - 'inline': Fix variable references in inline comments (// and /* *\/)
   *
   * @var string
   */
  public $fixCommentTypes = 'doc';

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

        // Fix variable references in preceding comments.
        $comment_tokens = $this->findPrecedingComment($phpcsFile, $stackPtr);
        $this->fixCommentVariable($phpcsFile, $comment_tokens, $var_name, $suggestion);
      }
      // @codeCoverageIgnoreEnd
    }
  }

  /**
   * Find preceding comment tokens for a variable.
   *
   * Searches backwards from the variable token, skipping whitespace, and
   * collects all comment tokens until a non-whitespace/non-comment token
   * is encountered.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $stackPtr
   *   The position of the variable token.
   *
   * @return array<int>
   *   Array of comment token positions, or empty array if none found.
   */
  private function findPrecedingComment(File $phpcsFile, int $stackPtr): array {
    $tokens = $phpcsFile->getTokens();
    $comment_tokens = [];

    $comment_types = [
      T_COMMENT,
      T_DOC_COMMENT_OPEN_TAG,
      T_DOC_COMMENT_CLOSE_TAG,
      T_DOC_COMMENT_STAR,
      T_DOC_COMMENT_WHITESPACE,
      T_DOC_COMMENT_TAG,
      T_DOC_COMMENT_STRING,
    ];

    for ($i = $stackPtr - 1; $i >= 0; $i--) {
      $code = $tokens[$i]['code'];

      if ($code === T_WHITESPACE) {
        continue;
      }

      // Skip trailing comments on previous statements (e.g.,
      // `$other = 1; // comment`). A T_COMMENT whose preceding
      // non-whitespace token is on the same line is trailing, not preceding.
      if ($code === T_COMMENT) {
        $prev_non_ws = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, NULL, TRUE);
        if ($prev_non_ws !== FALSE && $tokens[$prev_non_ws]['line'] === $tokens[$i]['line']) {
          break;
        }
      }

      if (in_array($code, $comment_types, TRUE)) {
        $comment_tokens[] = $i;
        continue;
      }

      // Hit a non-whitespace, non-comment token — stop.
      break;
    }

    return $comment_tokens;
  }

  /**
   * Fix variable references in preceding comment tokens.
   *
   * Replaces occurrences of the old variable name with the new one in
   * comment tokens, respecting the configured comment types. Skips comments
   * that contain references to multiple distinct variables.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param array<int> $commentTokens
   *   Array of comment token positions.
   * @param string $oldName
   *   The old variable name (without $).
   * @param string $newName
   *   The new variable name (without $).
   *
   * @codeCoverageIgnore
   */
  private function fixCommentVariable(File $phpcsFile, array $commentTokens, string $oldName, string $newName): void {
    if ($commentTokens === []) {
      return;
    }

    $tokens = $phpcsFile->getTokens();
    $fix_types = array_map('trim', explode(',', $this->fixCommentTypes));

    // Collect text only from eligible comment token types to check for
    // multiple variables. This prevents unrelated neighboring comments
    // from blocking a valid fix.
    $all_text = '';
    foreach ($commentTokens as $ptr) {
      $code = $tokens[$ptr]['code'];
      $is_doc = ($code === T_DOC_COMMENT_STRING);
      $is_inline = ($code === T_COMMENT);
      if (($is_doc && in_array('doc', $fix_types, TRUE)) || ($is_inline && in_array('inline', $fix_types, TRUE))) {
        $all_text .= $tokens[$ptr]['content'];
      }
    }

    // Count distinct $variable references in the eligible comment text.
    preg_match_all('/\$[a-zA-Z_]\w*/', $all_text, $matches);
    $unique_vars = array_unique($matches[0]);
    if (count($unique_vars) > 1) {
      // Multiple variables in comment — skip (requires manual fix).
      return;
    }

    $pattern = '/\$' . preg_quote($oldName, '/') . '(?![a-zA-Z0-9_])/';

    foreach ($commentTokens as $ptr) {
      $token = $tokens[$ptr];
      $code = $token['code'];
      $content = $token['content'];

      // Check if this token type matches the configured fix types.
      $is_doc = ($code === T_DOC_COMMENT_STRING);
      $is_inline = ($code === T_COMMENT);

      if ($is_doc && !in_array('doc', $fix_types, TRUE)) {
        continue;
      }

      if ($is_inline && !in_array('inline', $fix_types, TRUE)) {
        continue;
      }

      // Only process tokens that can contain variable references.
      if (!$is_doc && !$is_inline) {
        continue;
      }

      if (preg_match($pattern, $content) === 1) {
        $new_content = (string) preg_replace($pattern, '$' . $newName, $content);
        $phpcsFile->fixer->replaceToken($ptr, $new_content);
      }
    }
  }

}
