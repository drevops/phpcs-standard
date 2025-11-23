<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional integration test for DataProviderOrderSniff.
 */
#[CoversNothing]
class DataProviderOrderSniffFunctionalTest extends FunctionalTestCase {

  /**
   * {@inheritdoc}
   */
  protected string $sniffSource = 'DrevOps.TestingPractices.DataProviderOrder';

  #[Group('smoke')]
  public function testSmoke(): void {
    $this->runPhpcs(static::$fixtures . DIRECTORY_SEPARATOR . 'DataProviderOrderValid.php');
  }

  /**
   * Tests that correct provider ordering passes.
   */
  public function testCorrectOrderingPasses(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'DataProviderOrderValid.php',
      []
    );
  }

  /**
   * Tests that incorrect provider ordering is detected.
   */
  public function testIncorrectOrderingDetected(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'DataProviderOrderInvalid.php',
      [
        [
          'message' => 'Data provider method "dataProviderUserLogin" (line 20) appears before test method "testUserLogin" (line 32). Providers should be defined after their test methods',
          'source' => 'DrevOps.TestingPractices.DataProviderOrder.ProviderBeforeTest',
          'fixable' => FALSE,
        ],
        [
          'message' => 'Data provider method "dataProviderEmailValidation" (line 41) appears before test method "testEmailValidation" (line 52). Providers should be defined after their test methods',
          'source' => 'DrevOps.TestingPractices.DataProviderOrder.ProviderBeforeTest',
          'fixable' => FALSE,
        ],
        [
          'message' => 'Data provider method "dataProviderAuthentication" (line 61) appears before test method "testAuthentication" (line 71). Providers should be defined after their test methods',
          'source' => 'DrevOps.TestingPractices.DataProviderOrder.ProviderBeforeTest',
          'fixable' => FALSE,
        ],
      ]
    );
  }

}
