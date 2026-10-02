<?php

declare(strict_types=1);

namespace DrevOps\Helpers;

use PHP_CodeSniffer\Files\File;

/**
 * Reads class-like declarations and their ancestors from a token stream.
 *
 * Names are assembled from token content because PHP_CodeSniffer 3 splits a
 * qualified name into T_STRING and T_NS_SEPARATOR tokens while
 * PHP_CodeSniffer 4 emits a single T_NAME_* token.
 */
final class ClassLikeParser {

  /**
   * Tokens that open a class-like declaration.
   */
  public const array CLASS_LIKE_TOKENS = [T_CLASS, T_ANON_CLASS, T_INTERFACE, T_TRAIT, T_ENUM];

  /**
   * Tokens that form a class name.
   *
   * T_NAMESPACE covers the 'namespace\' prefix of a relative name, which
   * PHP_CodeSniffer 3 emits as a separate token.
   */
  protected const array NAME_TOKENS = [
    T_STRING,
    T_NS_SEPARATOR,
    T_NAME_QUALIFIED,
    T_NAME_FULLY_QUALIFIED,
    T_NAME_RELATIVE,
    T_NAMESPACE,
  ];

  /**
   * Parses the class-like declarations of a file.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file to parse.
   *
   * @return array<int, \DrevOps\Helpers\ClassLikeDeclaration>
   *   Declarations keyed by the position of the declaring token.
   */
  public function parse(File $phpcs_file): array {
    $tokens = $phpcs_file->getTokens();
    $namespace = '';
    $imports = [];
    $declarations = [];

    for ($ptr = 0; $ptr < $phpcs_file->numTokens; $ptr++) {
      $token = $tokens[$ptr];

      if ($token['code'] === T_NAMESPACE && $this->isNamespaceDeclaration($phpcs_file, $ptr)) {
        $namespace = $this->readNamespaceName($phpcs_file, $ptr);
        $imports = [];
      }
      elseif ($token['code'] === T_USE && $this->isImport($phpcs_file, $ptr)) {
        $imports = array_merge($imports, $this->readImports($phpcs_file, $ptr));
      }
      elseif (in_array($token['code'], self::CLASS_LIKE_TOKENS, TRUE) && isset($token['scope_opener'], $token['scope_closer'])) {
        $declarations[$ptr] = $this->readDeclaration($phpcs_file, $ptr, $namespace, $imports);
      }
    }

    return $declarations;
  }

  /**
   * Checks if a T_NAMESPACE token declares a namespace.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being parsed.
   * @param int $namespace_ptr
   *   The position of the T_NAMESPACE token.
   *
   * @return bool
   *   TRUE for a declaration, FALSE for the prefix of a relative name.
   */
  protected function isNamespaceDeclaration(File $phpcs_file, int $namespace_ptr): bool {
    $tokens = $phpcs_file->getTokens();

    return ($tokens[$namespace_ptr + 1]['code'] ?? NULL) !== T_NS_SEPARATOR;
  }

  /**
   * Reads the name of a namespace declaration.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being parsed.
   * @param int $namespace_ptr
   *   The position of the T_NAMESPACE token.
   *
   * @return string
   *   The namespace name, or an empty string for the global namespace.
   */
  protected function readNamespaceName(File $phpcs_file, int $namespace_ptr): string {
    $end_ptr = $phpcs_file->findNext([T_SEMICOLON, T_OPEN_CURLY_BRACKET], $namespace_ptr + 1);
    $names = $this->readNames($phpcs_file, $namespace_ptr + 1, $end_ptr === FALSE ? $phpcs_file->numTokens : $end_ptr);

    return ltrim($names[0] ?? '', '\\');
  }

  /**
   * Checks if a T_USE token starts an import statement.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being parsed.
   * @param int $use_ptr
   *   The position of the T_USE token.
   *
   * @return bool
   *   TRUE for an import, FALSE for a trait use or a closure use clause.
   */
  protected function isImport(File $phpcs_file, int $use_ptr): bool {
    $tokens = $phpcs_file->getTokens();

    foreach ($tokens[$use_ptr]['conditions'] as $condition_code) {
      if ($condition_code !== T_NAMESPACE) {
        return FALSE;
      }
    }

    $next_ptr = $phpcs_file->findNext(T_WHITESPACE, $use_ptr + 1, NULL, TRUE);

    return $next_ptr !== FALSE && $tokens[$next_ptr]['code'] !== T_OPEN_PARENTHESIS;
  }

  /**
   * Reads the class imports of an import statement.
   *
   * Function and constant imports are skipped.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being parsed.
   * @param int $use_ptr
   *   The position of the T_USE token.
   *
   * @return array<string, string>
   *   Fully qualified names keyed by lowercase alias.
   */
  protected function readImports(File $phpcs_file, int $use_ptr): array {
    $tokens = $phpcs_file->getTokens();
    $end_ptr = $phpcs_file->findNext(T_SEMICOLON, $use_ptr + 1);

    if ($end_ptr === FALSE) {
      return [];
    }

    $imports = [];
    $prefix = '';
    $name = '';
    $alias = '';
    $is_alias = FALSE;
    $is_skipped = FALSE;
    $is_in_group = FALSE;

    for ($i = $use_ptr + 1; $i <= $end_ptr; $i++) {
      $code = $tokens[$i]['code'];

      if ($code === T_OPEN_USE_GROUP) {
        $prefix = $name;
        $name = '';
        $is_in_group = TRUE;
        continue;
      }

      if ($code === T_AS) {
        $is_alias = TRUE;
        continue;
      }

      if (in_array($code, [T_COMMA, T_CLOSE_USE_GROUP, T_SEMICOLON], TRUE)) {
        if ($name !== '' && !$is_skipped) {
          $full_name = ltrim($prefix . $name, '\\');
          $imports[strtolower($alias === '' ? $this->getShortName($full_name) : $alias)] = $full_name;
        }

        $name = '';
        $alias = '';
        $is_alias = FALSE;
        $is_skipped = FALSE;
        continue;
      }

      if (!in_array($code, self::NAME_TOKENS, TRUE)) {
        continue;
      }

      if ($this->isImportKindKeyword($phpcs_file, $i, $name === '' && !$is_alias)) {
        // A keyword before a group applies to every entry of the statement.
        if (!$is_in_group) {
          return [];
        }

        $is_skipped = TRUE;
        continue;
      }

      if ($is_alias) {
        $alias .= $tokens[$i]['content'];
      }
      else {
        $name .= $tokens[$i]['content'];
      }
    }

    return $imports;
  }

  /**
   * Checks if a token is the 'function' or 'const' keyword of an import.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being parsed.
   * @param int $ptr
   *   The position of the token.
   * @param bool $is_entry_start
   *   Whether the token starts an import entry.
   *
   * @return bool
   *   TRUE if the token marks a function or constant import.
   */
  protected function isImportKindKeyword(File $phpcs_file, int $ptr, bool $is_entry_start): bool {
    $tokens = $phpcs_file->getTokens();

    if (!$is_entry_start || $tokens[$ptr]['code'] !== T_STRING) {
      return FALSE;
    }

    if (!in_array(strtolower($tokens[$ptr]['content']), ['function', 'const'], TRUE)) {
      return FALSE;
    }

    // PHP_CodeSniffer 3 splits a namespace segment named 'Function' into a
    // T_STRING followed by T_NS_SEPARATOR.
    return ($tokens[$ptr + 1]['code'] ?? NULL) !== T_NS_SEPARATOR;
  }

  /**
   * Reads a class-like declaration.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being parsed.
   * @param int $class_ptr
   *   The position of the declaring token.
   * @param string $namespace
   *   The namespace in effect.
   * @param array<string, string> $imports
   *   Fully qualified names keyed by lowercase alias.
   *
   * @return \DrevOps\Helpers\ClassLikeDeclaration
   *   The declaration.
   */
  protected function readDeclaration(File $phpcs_file, int $class_ptr, string $namespace, array $imports): ClassLikeDeclaration {
    $tokens = $phpcs_file->getTokens();
    $opener = $tokens[$class_ptr]['scope_opener'];
    $closer = $tokens[$class_ptr]['scope_closer'];

    $name = NULL;

    if ($tokens[$class_ptr]['code'] !== T_ANON_CLASS) {
      $short_name = (string) $phpcs_file->getDeclarationName($class_ptr);
      $name = $short_name === '' ? NULL : ltrim($namespace . '\\' . $short_name, '\\');
    }

    // Constructor arguments of an anonymous class can hold another anonymous
    // class with its own 'extends' keyword.
    $header_ptr = $tokens[$class_ptr]['parenthesis_closer'] ?? $class_ptr;
    $extends_ptr = $phpcs_file->findNext(T_EXTENDS, $header_ptr, $opener);
    $implements_ptr = $phpcs_file->findNext(T_IMPLEMENTS, $header_ptr, $opener);

    $ancestor_names = [];

    if ($extends_ptr !== FALSE) {
      $ancestor_names = $this->readNames($phpcs_file, $extends_ptr + 1, $implements_ptr === FALSE ? $opener : $implements_ptr);
    }

    if ($implements_ptr !== FALSE) {
      $ancestor_names = array_merge($ancestor_names, $this->readNames($phpcs_file, $implements_ptr + 1, $opener));
    }

    $methods = [];

    for ($i = $opener + 1; $i < $closer; $i++) {
      if (array_key_last($tokens[$i]['conditions']) !== $class_ptr) {
        continue;
      }

      if ($tokens[$i]['code'] === T_USE) {
        $end_ptr = $phpcs_file->findNext([T_SEMICOLON, T_OPEN_CURLY_BRACKET], $i + 1, $closer);
        $ancestor_names = array_merge($ancestor_names, $this->readNames($phpcs_file, $i + 1, $end_ptr === FALSE ? $closer : $end_ptr));
      }
      elseif ($tokens[$i]['code'] === T_FUNCTION && $phpcs_file->getMethodProperties($i)['scope'] !== 'private') {
        $method_name = strtolower((string) $phpcs_file->getDeclarationName($i));
        $methods[$method_name] = array_column($phpcs_file->getMethodParameters($i), 'name');
      }
    }

    $ancestors = array_map(fn(string $ancestor_name): string => $this->resolveName($ancestor_name, $namespace, $imports), $ancestor_names);

    return new ClassLikeDeclaration($name, $ancestors, $methods);
  }

  /**
   * Reads comma-separated names between two positions.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being parsed.
   * @param int $start
   *   The position to start reading from.
   * @param int $end
   *   The position to stop reading at (exclusive).
   *
   * @return list<string>
   *   The names as written.
   */
  protected function readNames(File $phpcs_file, int $start, int $end): array {
    $tokens = $phpcs_file->getTokens();
    $names = [];
    $name = '';

    for ($i = $start; $i < $end; $i++) {
      if ($tokens[$i]['code'] === T_COMMA) {
        $names[] = $name;
        $name = '';
      }
      elseif (in_array($tokens[$i]['code'], self::NAME_TOKENS, TRUE)) {
        $name .= $tokens[$i]['content'];
      }
    }

    $names[] = $name;

    return array_values(array_filter($names, static fn(string $name): bool => $name !== ''));
  }

  /**
   * Resolves a class name against the namespace and imports in effect.
   *
   * @param string $name
   *   The class name as written.
   * @param string $namespace
   *   The namespace in effect.
   * @param array<string, string> $imports
   *   Fully qualified names keyed by lowercase alias.
   *
   * @return string
   *   The fully qualified name without a leading backslash.
   */
  protected function resolveName(string $name, string $namespace, array $imports): string {
    if (str_starts_with($name, '\\')) {
      return ltrim($name, '\\');
    }

    if (strncasecmp($name, 'namespace\\', 10) === 0) {
      return ltrim($namespace . '\\' . substr($name, 10), '\\');
    }

    $segments = explode('\\', $name, 2);
    $alias = strtolower($segments[0]);

    if (isset($imports[$alias])) {
      return $imports[$alias] . (isset($segments[1]) ? '\\' . $segments[1] : '');
    }

    return ltrim($namespace . '\\' . $name, '\\');
  }

  /**
   * Gets the last segment of a qualified name.
   *
   * @param string $name
   *   The qualified name.
   *
   * @return string
   *   The last segment.
   */
  protected function getShortName(string $name): string {
    $position = strrpos($name, '\\');

    return $position === FALSE ? $name : substr($name, $position + 1);
  }

}
