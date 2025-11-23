<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test fixture with valid data provider ordering.
 */
class DataProviderOrderValid extends TestCase {

  /**
   * Test that comes before its provider.
   *
   * @dataProvider dataProviderUserLogin
   */
  public function testUserLogin($user, $pass): void {
    $this->assertNotEmpty($user);
  }

  /**
   * Data provider that comes after test.
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderUserLogin(): array {
    return [
      ['user1', 'pass1'],
      ['user2', 'pass2'],
    ];
  }

  /**
   * Another test before its provider.
   *
   * @dataProvider dataProviderEmailValidation
   */
  public function testEmailValidation($email): void {
    $this->assertNotEmpty($email);
  }

  /**
   * Helper method can be between test and provider.
   */
  private function helperValidate(): void {
    // Helper logic.
  }

  /**
   * Provider after helper method is OK.
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderEmailValidation(): array {
    return [
      ['test@example.com'],
    ];
  }

  /**
   * Test using attribute.
   */
  #[DataProvider('dataProviderAuthentication')]
  public function testAuthentication($scenario): void {
    $this->assertIsString($scenario);
  }

  /**
   * Provider for attribute test.
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderAuthentication(): array {
    return [
      ['scenario1'],
    ];
  }

}
