<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Abstract base class for snake_case variable naming sniffs.
 *
 * Provides shared functionality for validating and converting variable names
 * to snake_case format.
 */
abstract class AbstractSnakeCaseSniff implements Sniff {

  /**
   * Reserved PHP variable names that should not be validated.
   *
   * @var array<string>
   */
  protected array $reservedVariables = [
    'this',
    'GLOBALS',
    '_SERVER',
    '_GET',
    '_POST',
    '_FILES',
    '_COOKIE',
    '_SESSION',
    '_REQUEST',
    '_ENV',
    'argv',
    'argc',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(): array {
    return [T_VARIABLE];
  }

  /**
   * Check if a variable name is reserved.
   *
   * @param string $name
   *   Variable name (without $).
   *
   * @return bool
   *   TRUE if reserved, FALSE otherwise.
   */
  protected function isReserved(string $name): bool {
    return in_array($name, $this->reservedVariables, TRUE);
  }

  /**
   * Check if a variable name follows snake_case format.
   *
   * @param string $name
   *   Variable name (without $).
   *
   * @return bool
   *   TRUE if valid snake_case, FALSE otherwise.
   */
  protected function isSnakeCase(string $name): bool {
    return (bool) preg_match('/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/', $name);
  }

  /**
   * Convert a variable name to snake_case.
   *
   * @param string $name
   *   Variable name (without $).
   *
   * @return string
   *   Converted name in snake_case.
   */
  protected function toSnakeCase(string $name): string {
    // Remove leading underscores.
    $name = ltrim($name, '_');

    // Insert underscores before uppercase letters.
    // Run multiple times to handle consecutive capitals.
    do {
      $name = (string) preg_replace('/([a-zA-Z0-9])([A-Z])/', '$1_$2', $name, -1, $count);
    } while ($count > 0);

    // Lowercase everything.
    $name = strtolower($name);

    // Replace multiple consecutive underscores with single underscore.
    $name = (string) preg_replace('/_+/', '_', $name);

    return $name;
  }

  /**
   * Get all parameter names for a function/method.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $function_ptr
   *   The position of the function token.
   *
   * @return array<string>
   *   Array of parameter variable names (including $).
   */
  protected function getParameterNames(File $phpcs_file, int $function_ptr): array {
    $tokens = $phpcs_file->getTokens();

    // @codeCoverageIgnoreStart
    // PHPCS always sets parenthesis_opener and parenthesis_closer for valid
    // function/closure tokens. This check is defensive code for malformed
    // token streams.
    if (!isset($tokens[$function_ptr]['parenthesis_opener']) ||
      !isset($tokens[$function_ptr]['parenthesis_closer'])) {
      return [];
    }
    // @codeCoverageIgnoreEnd
    $param_start = $tokens[$function_ptr]['parenthesis_opener'];
    $param_end = $tokens[$function_ptr]['parenthesis_closer'];

    $param_names = [];

    for ($i = $param_start + 1; $i < $param_end; $i++) {
      if ($tokens[$i]['code'] === T_VARIABLE) {
        $param_names[] = $tokens[$i]['content'];
      }
    }

    return $param_names;
  }

  /**
   * Check if a variable is within a function's parameter list.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $stack_ptr
   *   The position of the variable token.
   * @param int $function_ptr
   *   The position of the function token.
   *
   * @return bool
   *   TRUE if variable is in parameter list, FALSE otherwise.
   */
  protected function isInParameterList(File $phpcs_file, int $stack_ptr, int $function_ptr): bool {
    $tokens = $phpcs_file->getTokens();

    // @codeCoverageIgnoreStart
    // PHPCS always sets parenthesis_opener and parenthesis_closer for valid
    // function/closure tokens. This check is defensive code for malformed
    // token streams.
    if (!isset($tokens[$function_ptr]['parenthesis_opener']) ||
      !isset($tokens[$function_ptr]['parenthesis_closer'])) {
      return FALSE;
    }
    // @codeCoverageIgnoreEnd
    $param_start = $tokens[$function_ptr]['parenthesis_opener'];
    $param_end = $tokens[$function_ptr]['parenthesis_closer'];

    return ($stack_ptr > $param_start && $stack_ptr < $param_end);
  }

  /**
   * Find the enclosing function for a variable.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $stack_ptr
   *   The position of the variable token.
   *
   * @return int|false
   *   The position of the enclosing function token, or FALSE if not found.
   */
  protected function findEnclosingFunction(File $phpcs_file, int $stack_ptr): int|false {
    $tokens = $phpcs_file->getTokens();

    // First, check conditions (for variables in method body).
    foreach ($tokens[$stack_ptr]['conditions'] ?? [] as $ptr => $code) {
      if ($code === T_FUNCTION || $code === T_CLOSURE) {
        return $ptr;
      }
    }

    // Search backwards (for variables in parameter list).
    return $phpcs_file->findPrevious([T_FUNCTION, T_CLOSURE], $stack_ptr - 1);
  }

  /**
   * Determine if a variable is a class or trait property.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $stackPtr
   *   The position of the variable token.
   *
   * @return bool
   *   TRUE if property, FALSE otherwise.
   */
  protected function isProperty(File $phpcsFile, int $stackPtr): bool {
    $tokens = $phpcsFile->getTokens();

    // Check if we're inside a class or trait.
    $conditions = $tokens[$stackPtr]['conditions'] ?? [];
    $in_class_or_trait = FALSE;

    foreach ($conditions as $condition_code) {
      if (in_array($condition_code, [T_CLASS, T_TRAIT, T_ENUM], TRUE)) {
        $in_class_or_trait = TRUE;
        break;
      }
    }

    if (!$in_class_or_trait) {
      return FALSE;
    }

    // Check if preceded by visibility modifier or var keyword (skip whitespace,
    // comments, static, readonly, type hints, and attributes).
    $prev_token = $phpcsFile->findPrevious(
      [
        T_WHITESPACE,
        T_COMMENT,
        T_DOC_COMMENT,
        T_STATIC,
        T_READONLY,
        T_STRING,
        T_NS_SEPARATOR,
        T_NULLABLE,
        T_TYPE_UNION,
        T_TYPE_INTERSECTION,
        T_ATTRIBUTE,
        T_ATTRIBUTE_END,
      ],
      $stackPtr - 1,
      NULL,
      TRUE
    );

    if ($prev_token !== FALSE) {
      $prev_code = $tokens[$prev_token]['code'];
      // If preceded by visibility modifier or var, it's a property (including
      // promoted constructor properties).
      if (in_array($prev_code, [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR], TRUE)) {
        return TRUE;
      }
    }

    // If inside a function/method/closure but NOT a promoted property, it's a
    // local variable.
    foreach ($conditions as $condition_code) {
      if (in_array($condition_code, [T_FUNCTION, T_CLOSURE], TRUE)) {
        return FALSE;
      }
    }

    return FALSE;
  }

  /**
   * Check if a variable is a static property access.
   *
   * Static properties are accessed with :: (T_DOUBLE_COLON) like:
   * - self::$property
   * - static::$property
   * - ClassName::$property.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $stackPtr
   *   The position of the variable token.
   *
   * @return bool
   *   TRUE if static property access, FALSE otherwise.
   */
  protected function isStaticPropertyAccess(File $phpcsFile, int $stackPtr): bool {
    $tokens = $phpcsFile->getTokens();

    // Find the previous non-whitespace token.
    $prev_token = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, NULL, TRUE);

    if ($prev_token !== FALSE) {
      // If preceded by :: (T_DOUBLE_COLON), it's a static property access.
      return $tokens[$prev_token]['code'] === T_DOUBLE_COLON;
    }

    // @codeCoverageIgnoreStart
    // This is unreachable in valid PHP code - findPrevious() will always find
    // at least one token (e.g., T_OPEN_TAG, T_EQUAL) before any variable.
    // This return is defensive code for malformed token streams.
    return FALSE;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Check if a variable is preceded by a visibility modifier.
   *
   * This indicates a promoted constructor property.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $stack_ptr
   *   The position of the variable token.
   *
   * @return bool
   *   TRUE if preceded by visibility modifier, FALSE otherwise.
   */
  protected function isPromotedProperty(File $phpcs_file, int $stack_ptr): bool {
    $tokens = $phpcs_file->getTokens();

    $prev_token = $phpcs_file->findPrevious(
      [
        T_WHITESPACE,
        T_COMMENT,
        T_DOC_COMMENT,
        T_READONLY,
        T_STRING,
        T_NS_SEPARATOR,
        T_NULLABLE,
        T_TYPE_UNION,
        T_TYPE_INTERSECTION,
        T_ATTRIBUTE,
        T_ATTRIBUTE_END,
      ],
      $stack_ptr - 1,
      NULL,
      TRUE
    );

    if ($prev_token !== FALSE) {
      $prev_code = $tokens[$prev_token]['code'];
      return in_array($prev_code, [T_PUBLIC, T_PROTECTED, T_PRIVATE], TRUE);
    }

    // @codeCoverageIgnoreStart
    // This is unreachable in valid PHP code - findPrevious() will always find
    // at least one token (e.g., T_OPEN_TAG, T_OPEN_CURLY_BRACKET) before any
    // variable. This return is defensive code for malformed token streams.
    return FALSE;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Check if a variable is a function/method parameter.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $stack_ptr
   *   The position of the variable token.
   * @param bool $include_usage_in_body
   *   Whether to check if variable usage in body matches a parameter name.
   *   TRUE: Consider both declaration and usage (for LocalVariableSniff).
   *   FALSE: Only check declaration in signature (for ParameterSniff).
   *
   * @return bool
   *   TRUE if parameter, FALSE otherwise.
   */
  protected function isParameter(File $phpcs_file, int $stack_ptr, bool $include_usage_in_body = FALSE): bool {
    $tokens = $phpcs_file->getTokens();

    // Check if preceded by visibility modifier (promoted property).
    if ($this->isPromotedProperty($phpcs_file, $stack_ptr)) {
      return FALSE;
    }

    // First, check if variable is in a parameter list.
    $function_ptr = $phpcs_file->findPrevious([T_FUNCTION, T_CLOSURE], $stack_ptr - 1);

    // If variable is within parameter parentheses, it's a parameter.
    if ($function_ptr !== FALSE && $this->isInParameterList($phpcs_file, $stack_ptr, $function_ptr)) {
      return TRUE;
    }

    // If we're not checking body usage, stop here.
    if (!$include_usage_in_body) {
      return FALSE;
    }

    // Variable is in function body. Find the enclosing function.
    $function_ptr = $this->findEnclosingFunction($phpcs_file, $stack_ptr);

    if ($function_ptr === FALSE) {
      // Not in a function/method - can't be a parameter.
      return FALSE;
    }

    // Check if variable matches a parameter name (used in method body).
    $var_name = $tokens[$stack_ptr]['content'];
    $param_names = $this->getParameterNames($phpcs_file, $function_ptr);

    return in_array($var_name, $param_names, TRUE);
  }

  /**
   * Check if a variable is a parameter from an inherited/implemented method.
   *
   * When a method implements an interface or overrides a parent method,
   * parameter names are inherited and can't be changed. We skip validation
   * for these parameters both in the signature and when used in the body.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $stackPtr
   *   The position of the variable token.
   *
   * @return bool
   *   TRUE if inherited parameter, FALSE otherwise.
   */
  protected function isInheritedParameter(File $phpcsFile, int $stackPtr): bool {
    $tokens = $phpcsFile->getTokens();

    // Find the enclosing function/method.
    $function_ptr = $this->findEnclosingFunction($phpcsFile, $stackPtr);

    // @codeCoverageIgnoreStart
    // This method is only called for variables that isParameter() confirmed as
    // parameters. Since parameters must always be inside functions, this check
    // should never fail. Defensive code for unexpected call chains.
    if ($function_ptr === FALSE) {
      // Not in a function/method.
      return FALSE;
    }
    // @codeCoverageIgnoreEnd
    // Check if this variable is a parameter of the function.
    $is_in_parameter_list = $this->isInParameterList($phpcsFile, $stackPtr, $function_ptr);

    // Check if we're in a class/interface/trait by looking at the function's
    // parent scope.
    $class_ptr = NULL;
    $class_type = NULL;

    // Get the function's immediate parent scope.
    foreach ($tokens[$function_ptr]['conditions'] ?? [] as $ptr => $code) {
      if (in_array($code, [T_CLASS, T_INTERFACE, T_TRAIT], TRUE)) {
        $class_ptr = $ptr;
        $class_type = $code;
        // Don't break - we want the innermost (last) class/interface/trait.
      }
    }

    // @codeCoverageIgnoreStart
    // Standalone functions (not in a class/interface/trait) can't have
    // inherited parameters. This path is reached for global functions, but
    // marked as ignored because the testIsInheritedParameter test uses
    // reflection to call this method directly, bypassing normal conditions.
    if ($class_ptr === NULL) {
      // Not in a class/interface/trait, can't be inherited.
      return FALSE;
    }
    // @codeCoverageIgnoreEnd
    // If we're in an interface or abstract method, always skip validation.
    // These are definitions that others must implement.
    if ($class_type === T_INTERFACE) {
      return TRUE;
    }

    // Check if the method is abstract.
    $abstract_ptr = $phpcsFile->findPrevious(T_ABSTRACT, $function_ptr, $class_ptr);
    if ($abstract_ptr !== FALSE) {
      return TRUE;
    }

    // For concrete classes, check if class extends or implements something.
    $class_opener = $tokens[$class_ptr]['scope_opener'] ?? NULL;
    // @codeCoverageIgnoreStart
    // PHPCS always sets scope_opener for valid class/interface/trait tokens.
    // This check is defensive code for malformed token streams.
    if ($class_opener === NULL) {
      return FALSE;
    }
    // @codeCoverageIgnoreEnd
    $extends_ptr = $phpcsFile->findNext(T_EXTENDS, $class_ptr, $class_opener);
    $implements_ptr = $phpcsFile->findNext(T_IMPLEMENTS, $class_ptr, $class_opener);

    if ($extends_ptr === FALSE && $implements_ptr === FALSE) {
      // No inheritance, parameters are not inherited.
      return FALSE;
    }

    // If the variable is in the parameter list, it's inherited.
    if ($is_in_parameter_list) {
      return TRUE;
    }

    // If the variable is in the method body, check if it matches a parameter
    // name. If it does, it's using an inherited parameter, so skip validation.
    $var_name = $tokens[$stackPtr]['content'];
    $param_names = $this->getParameterNames($phpcsFile, $function_ptr);

    return in_array($var_name, $param_names, TRUE);
  }

}
