<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

/**
 * Test fixture with valid data provider naming.
 */
class DataProviderValid extends TestCase {

  /**
   * Tests something with valid data.
   *
   * @dataProvider dataProviderValidCases
   */
  public function testSomething($input, $expected): void {
    $this->assertEquals($expected, $input);
  }

  /**
   * Provides valid test cases.
   *
   * @return array<int, array<int, mixed>>
   */
  public function dataProviderValidCases(): array {
    return [
      ['input1', 'expected1'],
      ['input2', 'expected2'],
    ];
  }

  /**
   * Tests another thing with valid data.
   *
   * @dataProvider dataProviderAnotherSet
   */
  public function testAnother($value): void {
    $this->assertNotEmpty($value);
  }

  /**
   * Provides another set of test cases.
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderAnotherSet(): array {
    return [
      ['value1'],
      ['value2'],
    ];
  }

  /**
   * Tests with multiple data providers referenced.
   *
   * @dataProvider dataProviderForMultiple
   */
  public function testMultiple($data): void {
    $this->assertIsString($data);
  }

  /**
   * Another test using the same provider.
   *
   * @dataProvider dataProviderForMultiple
   */
  public function testMultipleAgain($data): void {
    $this->assertIsString($data);
  }

  /**
   * Provides data for multiple tests.
   *
   * @return array<int, array<int, string>>
   */
  public function dataProviderForMultiple(): array {
    return [
      ['data1'],
      ['data2'],
    ];
  }

}
