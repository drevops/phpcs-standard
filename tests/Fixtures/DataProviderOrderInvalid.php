<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test fixture with invalid data provider ordering.
 */
class DataProviderOrderInvalid extends TestCase {

  /**
   * Provider appears BEFORE test - VIOLATION.
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
   * Test that comes after its provider - WRONG ORDER.
   *
   * @dataProvider dataProviderUserLogin
   */
  public function testUserLogin($user, $pass): void {
    $this->assertNotEmpty($user);
  }

  /**
   * Another provider before test - VIOLATION.
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderEmailValidation(): array {
    return [
      ['test@example.com'],
    ];
  }

  /**
   * Test after provider - WRONG ORDER.
   *
   * @dataProvider dataProviderEmailValidation
   */
  public function testEmailValidation($email): void {
    $this->assertNotEmpty($email);
  }

  /**
   * Provider before test using attribute - VIOLATION.
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderAuthentication(): array {
    return [
      ['scenario1'],
    ];
  }

  /**
   * Test using attribute after provider - WRONG ORDER.
   */
  #[DataProvider('dataProviderAuthentication')]
  public function testAuthentication($scenario): void {
    $this->assertIsString($scenario);
  }

}
