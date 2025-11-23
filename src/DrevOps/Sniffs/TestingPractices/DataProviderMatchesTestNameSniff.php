<?php

declare(strict_types=1);

namespace DrevOps\Sniffs\TestingPractices;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Enforces that data provider method names match test method names.
 *
 * This sniff ensures that data provider methods have names that match the
 * test methods they serve. The provider name must end with the exact test
 * name (after removing "test" prefix).
 *
 * Examples:
 * - testUserLogin -> must use provider ending with "UserLogin"
 * - dataProviderUserLogin ✓
 * - providerUserLogin ✓
 * - userLogin ✓
 * - dataProviderUserLoginCases ✗ (has suffix)
 * - dataProviderLogin ✗ (partial match).
 */
class DataProviderMatchesTestNameSniff implements Sniff {

  /**
   * Error code for invalid provider name.
   */
  private const CODE_INVALID_PROVIDER_NAME = 'InvalidProviderName';

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
    // Skip if not in a test class.
    if (!$this->isTestClass($phpcsFile, $stackPtr)) {
      return;
    }

    // Get the method name.
    $method_name_ptr = $phpcsFile->findNext(T_STRING, $stackPtr + 1, $stackPtr + 3);
    // @codeCoverageIgnoreStart
    if ($method_name_ptr === FALSE) {
      return;
    }
    // @codeCoverageIgnoreEnd
    $method_name = $phpcsFile->getTokens()[$method_name_ptr]['content'];

    // Skip if not a test method.
    if (!$this->isTestMethod($method_name)) {
      return;
    }

    // Extract test name (remove "test" prefix).
    $test_name = $this->extractTestName($method_name);

    // Find data provider annotation or attribute.
    $provider_name = $this->findDataProviderAnnotation($phpcsFile, $stackPtr);
    if ($provider_name === NULL) {
      $provider_name = $this->findDataProviderAttribute($phpcsFile, $stackPtr);
    }

    // Skip if no provider found or external provider.
    if ($provider_name === NULL) {
      return;
    }

    // Check if provider name matches test name.
    if (!$this->providerMatchesTest($provider_name, $test_name)) {
      $error = 'Data provider method "%s" does not match test method "%s". Expected provider name to end with "%s"';
      $data = [$provider_name, $method_name, $test_name];
      $phpcsFile->addError($error, $method_name_ptr, self::CODE_INVALID_PROVIDER_NAME, $data);
    }
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
    // @codeCoverageIgnoreStart
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
    // @codeCoverageIgnoreEnd
  }

  /**
   * Checks if a method is a test method.
   *
   * @param string $methodName
   *   The method name to check.
   *
   * @return bool
   *   TRUE if the method is a test method, FALSE otherwise.
   */
  private function isTestMethod(string $methodName): bool {
    // Test methods must start with "test" followed by uppercase letter.
    return preg_match('/^test[A-Z]/', $methodName) === 1;
  }

  /**
   * Extracts the test name from a test method name.
   *
   * @param string $methodName
   *   The test method name (e.g., "testUserLogin").
   *
   * @return string
   *   The test name without "test" prefix (e.g., "UserLogin").
   */
  private function extractTestName(string $methodName): string {
    return substr($methodName, 4);
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

  /**
   * Checks if provider name matches test name.
   *
   * @param string $providerName
   *   The provider method name.
   * @param string $testName
   *   The test name (without "test" prefix).
   *
   * @return bool
   *   TRUE if provider ends with exact test name, FALSE otherwise.
   */
  private function providerMatchesTest(string $providerName, string $testName): bool {
    // Provider name must end with exact test name (case-sensitive).
    return str_ends_with($providerName, $testName);
  }

}
