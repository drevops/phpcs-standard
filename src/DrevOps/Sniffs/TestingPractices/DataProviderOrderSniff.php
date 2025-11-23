<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\TestingPractices;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Enforces ordering of data provider and test methods.
 *
 * This sniff ensures structural organization of test and data provider
 * methods in the file. Helper methods between tests and providers are allowed.
 *
 * Configuration:
 * - providerPosition: "after" (default) or "before"
 *
 * Examples (providerPosition="after"):
 * ✓ testUserLogin() then dataProviderUserLogin()
 * ✗ dataProviderUserLogin() then testUserLogin()
 *
 * Examples (providerPosition="before"):
 * ✓ dataProviderUserLogin() then testUserLogin()
 * ✗ testUserLogin() then dataProviderUserLogin().
 */
class DataProviderOrderSniff implements Sniff {

  /**
   * Expected position of data provider relative to test.
   *
   * @var string
   */
  public $providerPosition = 'after';

  /**
   * Error code for provider appearing before test.
   */
  private const CODE_PROVIDER_BEFORE_TEST = 'ProviderBeforeTest';

  /**
   * Error code for provider appearing after test.
   */
  private const CODE_PROVIDER_AFTER_TEST = 'ProviderAfterTest';

  /**
   * {@inheritdoc}
   */
  public function register(): array {
    return [T_CLASS];
  }

  /**
   * {@inheritdoc}
   */
  public function process(File $phpcsFile, $stackPtr): void {
    $tokens = $phpcsFile->getTokens();

    // Skip if not a test class.
    if (!$this->isTestClass($phpcsFile, $stackPtr)) {
      return;
    }

    // Get class boundaries.
    $class_start = $tokens[$stackPtr]['scope_opener'] ?? NULL;
    $class_end = $tokens[$stackPtr]['scope_closer'] ?? NULL;

    // @codeCoverageIgnoreStart
    if ($class_start === NULL || $class_end === NULL) {
      return;
    }
    // @codeCoverageIgnoreEnd
    // Build map of tests and their providers with line numbers.
    $tests = $this->findTestsAndProviders($phpcsFile, $class_start, $class_end);

    // Build map of provider methods with line numbers.
    $providers = $this->findProviderMethods($phpcsFile, $class_start, $class_end);

    // Validate order and report violations.
    $this->validateOrder($phpcsFile, $tests, $providers);
  }

  /**
   * Determines if the current class is a test class.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $stackPtr
   *   The position of the class token.
   *
   * @return bool
   *   TRUE if the class is a test class, FALSE otherwise.
   */
  private function isTestClass(File $phpcsFile, int $stackPtr): bool {
    $tokens = $phpcsFile->getTokens();

    // Get the class name.
    $class_name_ptr = $phpcsFile->findNext(T_STRING, $stackPtr + 1, $stackPtr + 3);
    // @codeCoverageIgnoreStart
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
    $extends_ptr = $phpcsFile->findNext(T_EXTENDS, $stackPtr + 1, $tokens[$stackPtr]['scope_opener']);
    if ($extends_ptr !== FALSE) {
      $parent_class_ptr = $phpcsFile->findNext(T_STRING, $extends_ptr + 1, $tokens[$stackPtr]['scope_opener']);
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
   * Finds test methods and their data providers with line numbers.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $classStart
   *   The position of the class opening brace.
   * @param int $classEnd
   *   The position of the class closing brace.
   *
   * @return array<string, array<string, mixed>>
   *   Map of test names to their provider info:
   *   ['testName' => ['provider' => 'providerName', 'line' => 123]].
   */
  private function findTestsAndProviders(File $phpcsFile, int $classStart, int $classEnd): array {
    $tokens = $phpcsFile->getTokens();
    $tests = [];

    // Scan for all function tokens in the class.
    $function_ptr = $classStart;
    while (($function_ptr = $phpcsFile->findNext(T_FUNCTION, $function_ptr + 1, $classEnd)) !== FALSE) {
      // Get method name.
      $method_name_ptr = $phpcsFile->findNext(T_STRING, $function_ptr + 1, $function_ptr + 3);
      // @codeCoverageIgnoreStart
      if ($method_name_ptr === FALSE) {
        continue;
      }
      // @codeCoverageIgnoreEnd
      $method_name = $tokens[$method_name_ptr]['content'];

      // Skip if not a test method.
      if (!preg_match('/^test[A-Z]/', $method_name)) {
        continue;
      }

      // Find data provider annotation or attribute.
      $provider_name = $this->findDataProviderAnnotation($phpcsFile, $function_ptr);
      if ($provider_name === NULL) {
        $provider_name = $this->findDataProviderAttribute($phpcsFile, $function_ptr);
      }

      // Skip if no provider or external provider.
      if ($provider_name === NULL) {
        continue;
      }

      // Store test info.
      $test_line = $tokens[$method_name_ptr]['line'];
      $tests[$method_name] = [
        'provider' => $provider_name,
        'line' => $test_line,
      ];
    }

    return $tests;
  }

  /**
   * Finds all provider methods with line numbers.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $classStart
   *   The position of the class opening brace.
   * @param int $classEnd
   *   The position of the class closing brace.
   *
   * @return array<string, int>
   *   Map of provider names to line numbers:
   *   ['providerName' => 456].
   */
  private function findProviderMethods(File $phpcsFile, int $classStart, int $classEnd): array {
    $tokens = $phpcsFile->getTokens();
    $providers = [];

    // Scan for all function tokens in the class.
    $function_ptr = $classStart;
    while (($function_ptr = $phpcsFile->findNext(T_FUNCTION, $function_ptr + 1, $classEnd)) !== FALSE) {
      // Get method name.
      $method_name_ptr = $phpcsFile->findNext(T_STRING, $function_ptr + 1, $function_ptr + 3);
      // @codeCoverageIgnoreStart
      if ($method_name_ptr === FALSE) {
        continue;
      }
      // @codeCoverageIgnoreEnd
      $method_name = $tokens[$method_name_ptr]['content'];

      // Store method line number.
      $providers[$method_name] = $tokens[$method_name_ptr]['line'];
    }

    return $providers;
  }

  /**
   * Validates that providers appear after their tests.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param array<string, array<string, mixed>> $tests
   *   Map of tests to provider info.
   * @param array<string, int> $providers
   *   Map of provider names to line numbers.
   */
  private function validateOrder(File $phpcsFile, array $tests, array $providers): void {
    // Track which providers we've already checked.
    $checked_providers = [];

    foreach ($tests as $test_name => $test_info) {
      $provider_name = $test_info['provider'];
      $test_line = $test_info['line'];

      // Ensure provider name is a string.
      // @codeCoverageIgnoreStart
      if (!is_string($provider_name)) {
        continue;
      }
      // @codeCoverageIgnoreEnd
      // Skip if provider already checked (shared provider).
      if (isset($checked_providers[$provider_name])) {
        continue;
      }

      // Mark as checked.
      $checked_providers[$provider_name] = TRUE;

      // Check if provider exists in this class.
      // @codeCoverageIgnoreStart
      if (!isset($providers[$provider_name])) {
        continue;
      }
      // @codeCoverageIgnoreEnd
      $provider_line = $providers[$provider_name];

      // Validate ordering based on configuration.
      $has_violation = FALSE;
      $error_code = '';
      $error_message = '';

      if ($this->providerPosition === 'after') {
        // Provider should be after test.
        if ($provider_line < $test_line) {
          $has_violation = TRUE;
          $error_code = self::CODE_PROVIDER_BEFORE_TEST;
          $error_message = 'Data provider method "%s" (line %d) appears before test method "%s" (line %d). Providers should be defined after their test methods';
        }
      }
      // @codeCoverageIgnoreStart
      elseif ($this->providerPosition === 'before') {
        // Provider should be before test.
        if ($provider_line > $test_line) {
          $has_violation = TRUE;
          $error_code = self::CODE_PROVIDER_AFTER_TEST;
          $error_message = 'Data provider method "%s" (line %d) appears after test method "%s" (line %d). Providers should be defined before their test methods';
        }
      }
      // @codeCoverageIgnoreEnd
      if ($has_violation) {
        $data = [$provider_name, $provider_line, $test_name, $test_line];

        // Find the provider function token to report error at.
        $tokens = $phpcsFile->getTokens();
        $error_ptr = NULL;
        foreach ($tokens as $ptr => $token) {
          if ($token['line'] === $provider_line && $token['code'] === T_STRING) {
            $error_ptr = (int) $ptr;
            break;
          }
        }

        if ($error_ptr !== NULL) {
          $phpcsFile->addError($error_message, $error_ptr, $error_code, $data);
        }
      }
    }
  }

  /**
   * Finds data provider from @dataProvider annotation.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $functionPtr
   *   The position of the function token.
   *
   * @return string|null
   *   The provider method name, or NULL if not found or external.
   */
  private function findDataProviderAnnotation(File $phpcsFile, int $functionPtr): ?string {
    $tokens = $phpcsFile->getTokens();

    // Search backward for docblock before function.
    $comment_end = $phpcsFile->findPrevious(T_DOC_COMMENT_CLOSE_TAG, $functionPtr - 1);
    if ($comment_end === FALSE) {
      return NULL;
    }

    $comment_start = $tokens[$comment_end]['comment_opener'] ?? NULL;
    // @codeCoverageIgnoreStart
    if ($comment_start === NULL) {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    // Look for @dataProvider tag in the docblock.
    for ($i = $comment_start; $i <= $comment_end; $i++) {
      // @codeCoverageIgnoreStart
      if ($tokens[$i]['code'] !== T_DOC_COMMENT_TAG) {
        continue;
      }

      if ($tokens[$i]['content'] !== '@dataProvider') {
        continue;
      }
      // @codeCoverageIgnoreEnd
      // Find the method name after the tag.
      $string_ptr = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $i + 1, $i + 3);
      // @codeCoverageIgnoreStart
      if ($string_ptr === FALSE) {
        continue;
      }
      // @codeCoverageIgnoreEnd
      $method_name = trim($tokens[$string_ptr]['content']);

      // Skip external providers (ClassName::methodName).
      if (strpos($method_name, '::') !== FALSE) {
        return NULL;
      }

      return $method_name;
    }

    // @codeCoverageIgnoreStart
    return NULL;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Finds data provider from #[DataProvider] attribute.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcsFile
   *   The file being scanned.
   * @param int $functionPtr
   *   The position of the function token.
   *
   * @return string|null
   *   The provider method name, or NULL if not found or external.
   */
  private function findDataProviderAttribute(File $phpcsFile, int $functionPtr): ?string {
    $tokens = $phpcsFile->getTokens();

    // Search backward for attribute before function.
    $attribute_ptr = $phpcsFile->findPrevious(T_ATTRIBUTE, $functionPtr - 1);
    if ($attribute_ptr === FALSE) {
      return NULL;
    }

    // Check if attribute is close enough to function (within 10 tokens).
    // @codeCoverageIgnoreStart
    if ($functionPtr - $attribute_ptr > 10) {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    // Find the attribute name.
    $name_ptr = $phpcsFile->findNext(T_STRING, $attribute_ptr + 1, $functionPtr);
    // @codeCoverageIgnoreStart
    if ($name_ptr === FALSE || $tokens[$name_ptr]['content'] !== 'DataProvider') {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    // Find the opening parenthesis of attribute.
    $open_paren = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $name_ptr + 1, $functionPtr);
    // @codeCoverageIgnoreStart
    if ($open_paren === FALSE) {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    // Find the string inside attribute (provider method name).
    $string_ptr = $phpcsFile->findNext(T_CONSTANT_ENCAPSED_STRING, $open_paren + 1, $functionPtr);
    // @codeCoverageIgnoreStart
    if ($string_ptr === FALSE) {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    // Extract method name from string (remove quotes).
    $method_name = trim($tokens[$string_ptr]['content'], '\'"');

    // Skip external providers (ClassName::methodName).
    // @codeCoverageIgnoreStart
    if (strpos($method_name, '::') !== FALSE) {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    return $method_name;
  }

}
