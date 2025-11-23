<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\TestingPractices;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Enforces naming conventions for PHPUnit data provider methods.
 *
 * This sniff ensures that data provider methods start with a configurable
 * prefix (default: "dataProvider"). It provides auto-fixing capabilities to
 * rename methods and update all references.
 */
class DataProviderPrefixSniff implements Sniff {

  /**
   * The prefix that data provider methods should start with.
   *
   * @var string
   */
  public $prefix = 'dataProvider';

  /**
   * Error code for invalid prefix violations.
   */
  private const CODE_INVALID_PREFIX = 'InvalidPrefix';

  /**
   * Cache of data providers found in the current file.
   *
   * @var array<string, bool>
   */
  private array $dataProviders = [];

  /**
   * Whether the data providers have been cached for the current file.
   */
  private bool $dataProvidersCached = FALSE;

  /**
   * The file currently being processed.
   */
  private ?File $currentFile = NULL;

  /**
   * {@inheritdoc}
   */
  public function register(): array {
    return [T_FUNCTION];
  }

  /**
   * {@inheritdoc}
   */
  public function process(File $phpcsFile, $stackPtr): void {
    // Reset cache if processing a new file.
    if ($this->currentFile !== $phpcsFile) {
      $this->currentFile = $phpcsFile;
      $this->dataProvidersCached = FALSE;
      $this->dataProviders = [];
    }

    // Skip if not in a test class.
    if (!$this->isTestClass($phpcsFile, $stackPtr)) {
      return;
    }

    // Build cache of data providers on first function.
    if (!$this->dataProvidersCached) {
      $this->dataProviders = $this->findDataProviders($phpcsFile);
      $this->dataProvidersCached = TRUE;
    }

    // Get the function name.
    $function_name_ptr = $phpcsFile->findNext(T_STRING, $stackPtr + 1, $stackPtr + 3);
    // @codeCoverageIgnoreStart
    // Anonymous functions/closures don't have names. This is defensive code
    // for such cases and malformed token streams.
    if ($function_name_ptr === FALSE) {
      return;
    }
    // @codeCoverageIgnoreEnd
    $function_name = $phpcsFile->getTokens()[$function_name_ptr]['content'];

    // Check if this method is a data provider.
    if (!isset($this->dataProviders[$function_name])) {
      return;
    }

    // Check if the name starts with the correct prefix.
    if ($this->hasCorrectPrefix($function_name)) {
      return;
    }

    // Suggest a new name.
    $suggested_name = $this->suggestName($function_name);

    $error = 'Data provider method "%s" should start with prefix "%s", suggested name: "%s"';
    $data = [$function_name, $this->prefix, $suggested_name];

    $fix = $phpcsFile->addFixableError($error, $function_name_ptr, self::CODE_INVALID_PREFIX, $data);

    // @codeCoverageIgnoreStart
    if ($fix === TRUE) {
      $this->fixProviderName($phpcsFile, $function_name, $suggested_name);
    }
    // @codeCoverageIgnoreEnd
  }

  /**
   * Determines if the current file contains a test class.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $stackPtr
   *   The position of the current token.
   *
   * @return bool
   *   TRUE if the file contains a test class, FALSE otherwise.
   */
  private function isTestClass(File $phpcsFile, int $stackPtr): bool {
    $tokens = $phpcsFile->getTokens();

    // Find the class token.
    $class_ptr = $phpcsFile->findPrevious(T_CLASS, $stackPtr);
    if ($class_ptr === FALSE) {
      return FALSE;
    }

    // Get the class name.
    $class_name_ptr = $phpcsFile->findNext(T_STRING, $class_ptr + 1, $class_ptr + 3);
    // @codeCoverageIgnoreStart
    // PHPCS always sets class names for valid class tokens. This check is
    // defensive code for malformed token streams.
    if ($class_name_ptr === FALSE) {
      return FALSE;
    }
    // @codeCoverageIgnoreEnd
    $class_name = $tokens[$class_name_ptr]['content'];

    // Check if class name ends with Test or TestCase.
    if (preg_match('/Test(Case)?$/', $class_name) === 1) {
      return TRUE;
    }

    // Check if class extends TestCase or similar.
    $extends_ptr = $phpcsFile->findNext(T_EXTENDS, $class_ptr + 1, $tokens[$class_ptr]['scope_opener']);
    if ($extends_ptr !== FALSE) {
      $parent_class_ptr = $phpcsFile->findNext(T_STRING, $extends_ptr + 1, $tokens[$class_ptr]['scope_opener']);
      if ($parent_class_ptr !== FALSE) {
        $parent_class = $tokens[$parent_class_ptr]['content'];
        if (preg_match('/TestCase$/', $parent_class) === 1) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Extracts all data provider method names from @dataProvider annotations.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   *
   * @return array<string, bool>
   *   Array of data provider method names as keys.
   */
  private function findDataProviders(File $phpcsFile): array {
    $tokens = $phpcsFile->getTokens();
    $providers = [];

    // Search for @dataProvider annotations in doc comments.
    for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
      if ($tokens[$i]['code'] !== T_DOC_COMMENT_TAG) {
        continue;
      }

      if ($tokens[$i]['content'] !== '@dataProvider') {
        continue;
      }

      // Find the method name after the tag.
      $string_ptr = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $i + 1, $i + 3);
      if ($string_ptr === FALSE) {
        continue;
      }

      $method_name = trim($tokens[$string_ptr]['content']);

      // Handle method names that might include class references.
      // E.g., "ClassName::methodName" - we only want "methodName".
      if (strpos($method_name, '::') !== FALSE) {
        continue;
      }

      $providers[$method_name] = TRUE;
    }

    return $providers;
  }

  /**
   * Checks if a method name has the correct prefix.
   *
   * @param string $methodName
   *   The method name to check.
   *
   * @return bool
   *   TRUE if the name starts with the correct prefix, FALSE otherwise.
   */
  private function hasCorrectPrefix(string $methodName): bool {
    return str_starts_with($methodName, $this->prefix);
  }

  /**
   * Suggests a new name for a data provider method.
   *
   * @param string $currentName
   *   The current method name.
   *
   * @return string
   *   The suggested method name with the correct prefix.
   */
  private function suggestName(string $currentName): string {
    // Remove common prefixes.
    $name = $currentName;
    $common_prefixes = ['provider', 'provide', 'data', 'get'];

    foreach ($common_prefixes as $common_prefix) {
      if (str_starts_with(strtolower($name), $common_prefix)) {
        $name = substr($name, strlen($common_prefix));
        // Lowercase the first character if it's now uppercase.
        if ($name !== '' && ctype_upper($name[0])) {
          $name = lcfirst($name);
        }
        break;
      }
    }

    // Ensure we have a name after the prefix.
    if (empty($name)) {
      $name = $currentName;
    }

    // Add the configured prefix.
    return $this->prefix . ucfirst($name);
  }

  /**
   * Fixes the data provider method name throughout the file.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param string $oldName
   *   The old method name.
   * @param string $newName
   *   The new method name.
   *
   * @codeCoverageIgnore
   */
  private function fixProviderName(File $phpcsFile, string $oldName, string $newName): void {
    $tokens = $phpcsFile->getTokens();

    // Fix the method declaration.
    for ($i = 0; $i < $phpcsFile->numTokens; $i++) {
      // Fix method name in function declaration.
      if ($tokens[$i]['code'] === T_STRING && $tokens[$i]['content'] === $oldName) {
        // Check if this is a function declaration.
        $prev_ptr = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, NULL, TRUE);
        if ($prev_ptr !== FALSE && $tokens[$prev_ptr]['code'] === T_FUNCTION) {
          $phpcsFile->fixer->replaceToken($i, $newName);
        }
      }

      // Fix @dataProvider annotations.
      if ($tokens[$i]['code'] === T_DOC_COMMENT_STRING) {
        $content = $tokens[$i]['content'];
        // Check if this string is part of @dataProvider annotation.
        $prev_tag = $phpcsFile->findPrevious(T_DOC_COMMENT_TAG, $i - 1, $i - 3);
        if ($prev_tag !== FALSE && $tokens[$prev_tag]['content'] === '@dataProvider' && trim($content) === $oldName) {
          $phpcsFile->fixer->replaceToken($i, $newName);
        }
      }
    }
  }

}
