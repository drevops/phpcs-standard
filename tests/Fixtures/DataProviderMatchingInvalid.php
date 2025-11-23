<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test fixture with invalid data provider matching.
 */
class DataProviderMatchingInvalid extends TestCase {

  /**
   * Test with provider that doesn't match (partial match).
   *
   * @dataProvider dataProviderLogin
   */
  public function testUserLogin($user, $pass): void {
    $this->assertNotEmpty($user);
  }

  /**
   * Data provider with wrong name (partial match).
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderLogin(): array {
    return [
      ['user1', 'pass1'],
      ['user2', 'pass2'],
    ];
  }

  /**
   * Test with provider that has suffix.
   *
   * @dataProvider dataProviderEmailValidationCases
   */
  public function testEmailValidation($email): void {
    $this->assertNotEmpty($email);
  }

  /**
   * Data provider with suffix (not exact match).
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderEmailValidationCases(): array {
    return [
      ['test@example.com'],
      ['user@test.org'],
    ];
  }

  /**
   * Test with provider that doesn't match at all.
   *
   * @dataProvider providerAuth
   */
  public function testAuthenticationScenarios($scenario): void {
    $this->assertIsString($scenario);
  }

  /**
   * Data provider with completely different name.
   *
   * @return array<int, array<int, string>>
   */
  public function providerAuth(): array {
    return [
      ['scenario1'],
      ['scenario2'],
    ];
  }

  /**
   * Test using attribute with wrong name.
   */
  #[DataProvider('dataProviderPass')]
  public function testPasswordValidation($password): void {
    $this->assertNotEmpty($password);
  }

  /**
   * Data provider with wrong name (attribute).
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderPass(): array {
    return [
      ['password123'],
      ['securePass!'],
    ];
  }

  /**
   * Test using attribute with suffix.
   */
  #[DataProvider('TokenGenerationCases')]
  public function testTokenGeneration($token): void {
    $this->assertIsString($token);
  }

  /**
   * Data provider with suffix (attribute).
   *
   * @return array<int, array<int, string>>
   */
  public function TokenGenerationCases(): array {
    return [
      ['token1'],
      ['token2'],
    ];
  }

}
