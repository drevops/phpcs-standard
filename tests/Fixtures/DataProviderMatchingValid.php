<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test fixture with valid data provider matching.
 */
class DataProviderMatchingValid extends TestCase {

  /**
   * Test with provider name ending with exact test name.
   *
   * @dataProvider dataProviderUserLogin
   */
  public function testUserLogin($user, $pass): void {
    $this->assertNotEmpty($user);
  }

  /**
   * Data provider matching testUserLogin.
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
   * Test with different prefix.
   *
   * @dataProvider providerEmailValidation
   */
  public function testEmailValidation($email): void {
    $this->assertNotEmpty($email);
  }

  /**
   * Data provider with different prefix.
   *
   * @return array<int, array<int, string>>
   */
  public function providerEmailValidation(): array {
    return [
      ['test@example.com'],
      ['user@test.org'],
    ];
  }

  /**
   * Test with no prefix on provider.
   *
   * @dataProvider AuthenticationScenarios
   */
  public function testAuthenticationScenarios($scenario): void {
    $this->assertIsString($scenario);
  }

  /**
   * Data provider with no prefix.
   *
   * @return array<int, array<int, string>>
   */
  public function AuthenticationScenarios(): array {
    return [
      ['scenario1'],
      ['scenario2'],
    ];
  }

  /**
   * Test using PHP 8 attribute syntax.
   */
  #[DataProvider('dataProviderPasswordValidation')]
  public function testPasswordValidation($password): void {
    $this->assertNotEmpty($password);
  }

  /**
   * Data provider using attribute.
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderPasswordValidation(): array {
    return [
      ['password123'],
      ['securePass!'],
    ];
  }

  /**
   * Test using attribute with no prefix.
   */
  #[DataProvider('TokenGeneration')]
  public function testTokenGeneration($token): void {
    $this->assertIsString($token);
  }

  /**
   * Data provider for token generation.
   *
   * @return array<int, array<int, string>>
   */
  public function TokenGeneration(): array {
    return [
      ['token1'],
      ['token2'],
    ];
  }

}
