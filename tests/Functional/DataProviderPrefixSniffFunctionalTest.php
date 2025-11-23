<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional integration test for DataProviderPrefixSniff.
 *
 * This tests the sniff by actually running phpcs as an external command,
 * which is the most reliable way to test PHPCS sniffs.
 */
#[CoversNothing]
class DataProviderPrefixSniffFunctionalTest extends FunctionalTestCase {

  /**
   * {@inheritdoc}
   */
  protected string $sniffSource = 'DrevOps.TestingPractices.DataProviderPrefix';

  #[Group('smoke')]
  public function testSmoke(): void {
    $this->runPhpcs(static::$fixtures . DIRECTORY_SEPARATOR . 'DataProviderValid.php');
  }

  /**
   * Tests that valid data provider names pass without errors.
   */
  public function testValidDataProviderNamesPass(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'DataProviderValid.php',
      []
    );
  }

  /**
   * Tests that invalid data provider names are detected.
   */
  public function testInvalidDataProviderNamesAreDetected(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'DataProviderInvalid.php',
      [
        [
          'message' => 'Data provider method "providerInvalidCases" should start with prefix "dataProvider", suggested name: "dataProviderInvalidCases"',
          'source' => 'DrevOps.TestingPractices.DataProviderPrefix.InvalidPrefix',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Data provider method "casesForAnotherTest" should start with prefix "dataProvider", suggested name: "dataProviderCasesForAnotherTest"',
          'source' => 'DrevOps.TestingPractices.DataProviderPrefix.InvalidPrefix',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Data provider method "provideDataForTest" should start with prefix "dataProvider", suggested name: "dataProviderDataForTest"',
          'source' => 'DrevOps.TestingPractices.DataProviderPrefix.InvalidPrefix',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Data provider method "getTestData" should start with prefix "dataProvider", suggested name: "dataProviderTestData"',
          'source' => 'DrevOps.TestingPractices.DataProviderPrefix.InvalidPrefix',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Data provider method "sharedProvider" should start with prefix "dataProvider", suggested name: "dataProviderSharedProvider"',
          'source' => 'DrevOps.TestingPractices.DataProviderPrefix.InvalidPrefix',
          'fixable' => TRUE,
        ],
      ]
    );
  }

}
