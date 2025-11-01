<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Enforces snake_case naming for local variables and function parameters.
 *
 * This sniff checks that variables and parameters use snake_case format.
 * Class properties are intentionally excluded from this check.
 */
final class VariableSnakeCaseSniff implements Sniff {

  /**
   * Error code for non-snake_case variables.
   */
  public const string CODE_VARIABLE_NOT_SNAKE_CASE = 'VariableNotSnakeCase';

  /**
   * {@inheritdoc}
   */
  public function register(): array {
    return [T_VARIABLE];
  }

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

    // Skip class/trait properties - we only check local variables and params.
    if ($this->isProperty($phpcsFile, $stackPtr)) {
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
        self::CODE_VARIABLE_NOT_SNAKE_CASE,
        $data
      );

      // @codeCoverageIgnoreStart
      if ($fix === TRUE) {
        $phpcsFile->fixer->replaceToken($stackPtr, '$' . $suggestion);
      }
      // @codeCoverageIgnoreEnd
    }
  }

  /**
   * Check if a variable name is a reserved PHP variable.
   *
   * @param string $name
   *   The variable name (without $).
   *
   * @return bool
   *   TRUE if reserved, FALSE otherwise.
   */
  protected function isReserved(string $name): bool {
    $reserved = [
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

    return in_array($name, $reserved, TRUE);
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
    // First check conditions (for variables in method body).
    $function_ptr = NULL;
    foreach ($tokens[$stackPtr]['conditions'] ?? [] as $ptr => $code) {
      if ($code === T_FUNCTION || $code === T_CLOSURE) {
        $function_ptr = $ptr;
      }
    }

    // If not found in conditions, search backwards (for variables in
    // parameter list).
    if ($function_ptr === NULL) {
      $function_ptr = $phpcsFile->findPrevious([T_FUNCTION, T_CLOSURE], $stackPtr - 1);
    }

    if ($function_ptr === FALSE) {
      // Not in a function/method.
      return FALSE;
    }

    // Check if this variable is a parameter of the function.
    // @codeCoverageIgnoreStart
    if (!isset($tokens[$function_ptr]['parenthesis_opener']) ||
      !isset($tokens[$function_ptr]['parenthesis_closer'])) {
      return FALSE;
    }
    // @codeCoverageIgnoreEnd
    $param_start = $tokens[$function_ptr]['parenthesis_opener'];
    $param_end = $tokens[$function_ptr]['parenthesis_closer'];

    $is_in_parameter_list = ($stackPtr > $param_start && $stackPtr < $param_end);

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

    if ($class_ptr === NULL) {
      // Not in a class/interface/trait, can't be inherited.
      return FALSE;
    }

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

  /**
   * Get all parameter names for a function/method.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $functionPtr
   *   The position of the function token.
   *
   * @return array<string>
   *   Array of parameter variable names (including $).
   */
  protected function getParameterNames(File $phpcsFile, int $functionPtr): array {
    $tokens = $phpcsFile->getTokens();

    // @codeCoverageIgnoreStart
    if (!isset($tokens[$functionPtr]['parenthesis_opener']) ||
      !isset($tokens[$functionPtr]['parenthesis_closer'])) {
      return [];
    }
    // @codeCoverageIgnoreEnd
    $param_start = $tokens[$functionPtr]['parenthesis_opener'];
    $param_end = $tokens[$functionPtr]['parenthesis_closer'];

    $param_names = [];

    for ($i = $param_start + 1; $i < $param_end; $i++) {
      if ($tokens[$i]['code'] === T_VARIABLE) {
        $param_names[] = $tokens[$i]['content'];
      }
    }

    return $param_names;
  }

  /**
   * Check if a name is in valid snake_case format.
   *
   * @param string $name
   *   The variable name to check.
   *
   * @return bool
   *   TRUE if valid snake_case, FALSE otherwise.
   */
  protected function isSnakeCase(string $name): bool {
    // Valid snake_case: starts with lowercase letter, followed by lowercase
    // letters, digits, or underscores. No consecutive underscores, no
    // uppercase letters.
    return (bool) preg_match('/^[a-z][a-z0-9]*(?:_[a-z0-9]+)*$/', $name);
  }

  /**
   * Convert a variable name to snake_case.
   *
   * @param string $name
   *   The variable name to convert.
   *
   * @return string
   *   The snake_case version of the name.
   */
  protected function toSnakeCase(string $name): string {
    // Convert camelCase to snake_case by inserting underscore before capitals.
    $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name;

    // Convert to lowercase.
    $snake = strtolower($snake);

    // Replace multiple consecutive underscores with single underscore.
    $snake = preg_replace('/_{2,}/', '_', $snake) ?? $snake;

    // Remove leading underscores.
    return ltrim($snake, '_');
  }

}
