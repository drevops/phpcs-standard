<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\NamingConventions;

use DrevOps\Helpers\InheritanceResolver;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Abstract base class for variable naming convention sniffs.
 *
 * Provides shared functionality for validating and converting variable names
 * to either snake_case or camelCase format based on configuration.
 */
abstract class AbstractVariableNamingSniff implements Sniff {

  /**
   * The naming convention to enforce.
   *
   * Valid values: 'snakeCase', 'camelCase'
   *
   * @var string
   */
  public $format = 'snakeCase';

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
   * Resolves the parameter names that ancestors declare for a method.
   */
  protected ?InheritanceResolver $inheritanceResolver = NULL;

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
   * Check if a variable name has a leading underscore.
   *
   * Variables with leading underscores (e.g., $_static_value) are typically
   * used as a naming convention for internal/special variables and should
   * be excluded from naming convention checks.
   *
   * @param string $name
   *   Variable name (without $).
   *
   * @return bool
   *   TRUE if has leading underscore, FALSE otherwise.
   */
  protected function hasLeadingUnderscore(string $name): bool {
    return str_starts_with($name, '_');
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
   * Check if a variable name follows camelCase format.
   *
   * @param string $name
   *   Variable name (without $).
   *
   * @return bool
   *   TRUE if valid camelCase, FALSE otherwise.
   */
  protected function isCamelCase(string $name): bool {
    return (bool) preg_match('/^[a-z][a-zA-Z0-9]*$/', $name);
  }

  /**
   * Check if a variable name is valid for the configured format.
   *
   * @param string $name
   *   Variable name (without $).
   *
   * @return bool
   *   TRUE if valid for configured format, FALSE otherwise.
   */
  protected function isValidFormat(string $name): bool {
    return match ($this->format) {
      'snakeCase' => $this->isSnakeCase($name),
      'camelCase' => $this->isCamelCase($name),
      default => throw new \RuntimeException('Invalid format: ' . $this->format),
    };
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
   * Convert a variable name to camelCase.
   *
   * @param string $name
   *   Variable name (without $).
   *
   * @return string
   *   Converted name in camelCase.
   */
  protected function toCamelCase(string $name): string {
    // Remove leading underscores.
    $name = ltrim($name, '_');

    // Split on underscores and capitalize each part except the first.
    $parts = explode('_', $name);
    $first = strtolower(array_shift($parts));
    $rest = array_map('ucfirst', array_map('strtolower', $parts));

    return $first . implode('', $rest);
  }

  /**
   * Convert a variable name to the configured format.
   *
   * @param string $name
   *   Variable name (without $).
   *
   * @return string
   *   Converted name in the configured format.
   */
  protected function toFormat(string $name): string {
    return match ($this->format) {
      'snakeCase' => $this->toSnakeCase($name),
      'camelCase' => $this->toCamelCase($name),
      default => throw new \RuntimeException('Invalid format: ' . $this->format),
    };
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
   * Find the function whose parameter list contains a variable.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $stack_ptr
   *   The position of the variable token.
   *
   * @return int|false
   *   The position of the function token, or FALSE if the variable is not in
   *   a parameter list.
   */
  protected function findParameterListOwner(File $phpcs_file, int $stack_ptr): int|false {
    $function_ptr = $phpcs_file->findPrevious([T_FUNCTION, T_CLOSURE], $stack_ptr - 1);

    if ($function_ptr !== FALSE && $this->isInParameterList($phpcs_file, $stack_ptr, $function_ptr)) {
      return $function_ptr;
    }

    return FALSE;
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
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $stack_ptr
   *   The position of the variable token.
   *
   * @return bool
   *   TRUE if property, FALSE otherwise.
   */
  protected function isProperty(File $phpcs_file, int $stack_ptr): bool {
    $tokens = $phpcs_file->getTokens();

    // Check if we're inside a class or trait.
    $conditions = $tokens[$stack_ptr]['conditions'] ?? [];
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
    $prev_token = $phpcs_file->findPrevious(
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
        // PHP 8+ namespaced type tokens.
        T_NAME_FULLY_QUALIFIED,
        T_NAME_QUALIFIED,
        T_NAME_RELATIVE,
      ],
      $stack_ptr - 1,
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
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $stack_ptr
   *   The position of the variable token.
   *
   * @return bool
   *   TRUE if static property access, FALSE otherwise.
   */
  protected function isStaticPropertyAccess(File $phpcs_file, int $stack_ptr): bool {
    $tokens = $phpcs_file->getTokens();

    // Find the previous non-whitespace token.
    $prev_token = $phpcs_file->findPrevious(T_WHITESPACE, $stack_ptr - 1, NULL, TRUE);

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
        // PHP 8+ namespaced type tokens.
        T_NAME_FULLY_QUALIFIED,
        T_NAME_QUALIFIED,
        T_NAME_RELATIVE,
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

    // If variable is within parameter parentheses, it's a parameter.
    if ($this->findParameterListOwner($phpcs_file, $stack_ptr) !== FALSE) {
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
   * Check if a variable is a parameter whose name an ancestor declares.
   *
   * A parameter is inherited when an extended class, an implemented or
   * extended interface, or a used trait declares the same method with a
   * parameter of the same name. Renamed and additional parameters are not
   * inherited. When no resolved ancestor declares the method and an ancestor
   * cannot be resolved, every parameter of the method counts as inherited.
   * Applies to the parameter in the signature and to its uses in the body.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being scanned.
   * @param int $stack_ptr
   *   The position of the variable token.
   *
   * @return bool
   *   TRUE if inherited parameter, FALSE otherwise.
   */
  protected function isInheritedParameter(File $phpcs_file, int $stack_ptr): bool {
    $function_ptr = $this->findParameterListOwner($phpcs_file, $stack_ptr);

    if ($function_ptr === FALSE) {
      $function_ptr = $this->findEnclosingFunction($phpcs_file, $stack_ptr);
    }

    if ($function_ptr === FALSE) {
      return FALSE;
    }

    $var_name = $phpcs_file->getTokens()[$stack_ptr]['content'];

    if (!in_array($var_name, $this->getParameterNames($phpcs_file, $function_ptr), TRUE)) {
      return FALSE;
    }

    $this->inheritanceResolver ??= new InheritanceResolver();
    $inherited_names = $this->inheritanceResolver->getInheritedParameterNames($phpcs_file, $function_ptr);

    return $inherited_names === NULL || in_array($var_name, $inherited_names, TRUE);
  }

}
