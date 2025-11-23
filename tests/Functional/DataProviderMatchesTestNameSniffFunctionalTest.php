<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional integration test for DataProviderMatchesTestNameSniff.
 */
#[CoversNothing]
class DataProviderMatchesTestNameSniffFunctionalTest extends FunctionalTestCase {

  /**
   * {@inheritdoc}
   */
  protected string $sniffSource = 'DrevOps.TestingPractices.DataProviderMatchesTestName';

  #[Group('smoke')]
  public function testSmoke(): void {
    $this->runPhpcs(static::$fixtures . DIRECTORY_SEPARATOR . 'DataProviderMatchingValid.php');
  }

  /**
   * Tests that valid matching provider names pass.
   */
  public function testValidMatchingProvidersPass(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'DataProviderMatchingValid.php',
      []
    );
  }

  /**
   * Tests that invalid non-matching provider names are detected.
   */
  public function testInvalidNonMatchingProvidersDetected(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'DataProviderMatchingInvalid.php',
      [
        [
          'message' => 'Data provider method "dataProviderLogin" does not match test method "testUserLogin". Expected provider name to end with "UserLogin"',
          'source' => 'DrevOps.TestingPractices.DataProviderMatchesTestName.InvalidProviderName',
          'fixable' => FALSE,
        ],
        [
          'message' => 'Data provider method "dataProviderEmailValidationCases" does not match test method "testEmailValidation". Expected provider name to end with "EmailValidation"',
          'source' => 'DrevOps.TestingPractices.DataProviderMatchesTestName.InvalidProviderName',
          'fixable' => FALSE,
        ],
        [
          'message' => 'Data provider method "providerAuth" does not match test method "testAuthenticationScenarios". Expected provider name to end with "AuthenticationScenarios"',
          'source' => 'DrevOps.TestingPractices.DataProviderMatchesTestName.InvalidProviderName',
          'fixable' => FALSE,
        ],
        [
          'message' => 'Data provider method "dataProviderPass" does not match test method "testPasswordValidation". Expected provider name to end with "PasswordValidation"',
          'source' => 'DrevOps.TestingPractices.DataProviderMatchesTestName.InvalidProviderName',
          'fixable' => FALSE,
        ],
        [
          'message' => 'Data provider method "TokenGenerationCases" does not match test method "testTokenGeneration". Expected provider name to end with "TokenGeneration"',
          'source' => 'DrevOps.TestingPractices.DataProviderMatchesTestName.InvalidProviderName',
          'fixable' => FALSE,
        ],
      ]
    );
  }

}
