<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

/**
 * Test fixture with invalid data provider naming.
 */
class DataProviderInvalid extends TestCase {

  /**
   * Tests something with invalid data provider name.
   *
   * @dataProvider providerInvalidCases
   */
  public function testSomething($input, $expected): void {
    $this->assertEquals($expected, $input);
  }

  /**
   * Data provider with wrong prefix - should trigger violation.
   *
   * @return array<int, array<int, mixed>>
   */
  public function providerInvalidCases(): array {
    return [
      ['input1', 'expected1'],
      ['input2', 'expected2'],
    ];
  }

  /**
   * Tests another thing with no prefix.
   *
   * @dataProvider casesForAnotherTest
   */
  public function testAnother($value): void {
    $this->assertNotEmpty($value);
  }

  /**
   * Data provider with no prefix - should trigger violation.
   *
   * @return array<int, array<int, string>>
   */
  public function casesForAnotherTest(): array {
    return [
      ['value1'],
      ['value2'],
    ];
  }

  /**
   * Tests with data provider using "provide" prefix.
   *
   * @dataProvider provideDataForTest
   */
  public function testWithProvide($data): void {
    $this->assertIsString($data);
  }

  /**
   * Data provider with "provide" prefix - should trigger violation.
   *
   * @return array<int, array<int, string>>
   */
  public function provideDataForTest(): array {
    return [
      ['data1'],
      ['data2'],
    ];
  }

  /**
   * Tests with data provider using "get" prefix.
   *
   * @dataProvider getTestData
   */
  public function testWithGet($data): void {
    $this->assertIsString($data);
  }

  /**
   * Data provider with "get" prefix - should trigger violation.
   *
   * @return array<int, array<int, string>>
   */
  public function getTestData(): array {
    return [
      ['data1'],
      ['data2'],
    ];
  }

  /**
   * Tests with multiple references to the same wrong provider.
   *
   * @dataProvider sharedProvider
   */
  public function testShared1($data): void {
    $this->assertIsString($data);
  }

  /**
   * Another test using the same wrong provider.
   *
   * @dataProvider sharedProvider
   */
  public function testShared2($data): void {
    $this->assertIsString($data);
  }

  /**
   * Data provider referenced multiple times - should trigger violation.
   *
   * @return array<int, array<int, string>>
   */
  public function sharedProvider(): array {
    return [
      ['data1'],
      ['data2'],
    ];
  }

}
