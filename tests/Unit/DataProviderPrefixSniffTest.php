<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\TestingPractices\DataProviderPrefixSniff;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for DataProviderPrefixSniff.
 */
#[CoversClass(DataProviderPrefixSniff::class)]
class DataProviderPrefixSniffTest extends UnitTestCase {

  /**
   * The sniff instance.
   */
  private DataProviderPrefixSniff $sniff;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure to run only DataProviderPrefix sniff.
    $this->config->sniffs = ['DrevOps.TestingPractices.DataProviderPrefix'];
    $this->ruleset = new Ruleset($this->config);
    $this->sniff = new DataProviderPrefixSniff();
  }

  /**
   * Tests the register method.
   */
  public function testRegister(): void {
    $result = $this->sniff->register();
    $this->assertEquals([T_FUNCTION], $result);
  }

  /**
   * Tests isTestClass method with valid test class names.
   */
  public function testIsTestClassWithValidTestClassName(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  public function testSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertTrue($result);
  }

  /**
   * Tests isTestClass method with TestCase suffix.
   */
  public function testIsTestClassWithTestCaseSuffix(): void {
    $code = <<<'PHP'
<?php
class MyTestCase {
  public function testSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertTrue($result);
  }

  /**
   * Tests isTestClass method with class extending TestCase.
   */
  public function testIsTestClassWithExtendsTestCase(): void {
    $code = <<<'PHP'
<?php
use PHPUnit\Framework\TestCase;
class MyClass extends TestCase {
  public function testSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertTrue($result);
  }

  /**
   * Tests isTestClass method with non-test class.
   */
  public function testIsTestClassWithNonTestClass(): void {
    $code = <<<'PHP'
<?php
class MyClass {
  public function doSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertFalse($result);
  }

  /**
   * Tests findDataProviders method.
   */
  public function testFindDataProviders(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider providerTestData
   */
  public function testSomething($data) {}

  /**
   * @dataProvider anotherProvider
   */
  public function testAnother($data) {}

  public function providerTestData() {
    return [['data']];
  }

  public function anotherProvider() {
    return [['data']];
  }
}
PHP;

    $file = $this->processCode($code);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviders');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file);
    $this->assertIsArray($result);

    $this->assertArrayHasKey('providerTestData', $result);
    $this->assertArrayHasKey('anotherProvider', $result);
    $this->assertEquals(TRUE, $result['providerTestData']);
    $this->assertEquals(TRUE, $result['anotherProvider']);
  }

  /**
   * Tests findDataProviders with external class references.
   */
  public function testFindDataProvidersSkipsExternalReferences(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider ExternalClass::providerData
   */
  public function testSomething($data) {}

  /**
   * @dataProvider localProvider
   */
  public function testAnother($data) {}
}
PHP;

    $file = $this->processCode($code);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviders');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file);
    $this->assertIsArray($result);

    // Should only find localProvider, not ExternalClass::providerData.
    $this->assertArrayNotHasKey('ExternalClass::providerData', $result);
    $this->assertArrayHasKey('localProvider', $result);
  }

  /**
   * Tests hasCorrectPrefix method with correct prefix.
   */
  public function testHasCorrectPrefixWithCorrectPrefix(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('hasCorrectPrefix');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'dataProviderTest');
    $this->assertTrue($result);
  }

  /**
   * Tests hasCorrectPrefix method with wrong prefix.
   */
  public function testHasCorrectPrefixWithWrongPrefix(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('hasCorrectPrefix');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'providerTest');
    $this->assertFalse($result);
  }

  /**
   * Tests hasCorrectPrefix with custom prefix.
   */
  public function testHasCorrectPrefixWithCustomPrefix(): void {
    $this->sniff->prefix = 'provider';

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('hasCorrectPrefix');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'providerTest');
    $this->assertTrue($result);
  }

  /**
   * Tests suggestName method with "provider" prefix.
   */
  public function testSuggestNameWithProviderPrefix(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('suggestName');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'providerTestData');
    $this->assertEquals('dataProviderTestData', $result);
  }

  /**
   * Tests suggestName method with "provide" prefix.
   */
  public function testSuggestNameWithProvidePrefix(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('suggestName');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'provideTestData');
    $this->assertEquals('dataProviderTestData', $result);
  }

  /**
   * Tests suggestName method with "data" prefix.
   */
  public function testSuggestNameWithDataPrefix(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('suggestName');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'dataForTest');
    $this->assertEquals('dataProviderForTest', $result);
  }

  /**
   * Tests suggestName method with "get" prefix.
   */
  public function testSuggestNameWithGetPrefix(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('suggestName');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'getTestData');
    $this->assertEquals('dataProviderTestData', $result);
  }

  /**
   * Tests suggestName method with no known prefix.
   */
  public function testSuggestNameWithNoKnownPrefix(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('suggestName');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'testCases');
    $this->assertEquals('dataProviderTestCases', $result);
  }

  /**
   * Tests process method with valid data provider name.
   */
  public function testProcessWithValidDataProviderName(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider dataProviderTestData
   */
  public function testSomething($data) {}

  public function dataProviderTestData() {
    return [['data']];
  }
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    // Should not have any errors.
    $this->assertEmpty($errors);
  }

  /**
   * Tests process method with invalid data provider name.
   */
  public function testProcessWithInvalidDataProviderName(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider providerTestData
   */
  public function testSomething($data) {}

  public function providerTestData() {
    return [['data']];
  }
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    // Should have one error.
    $this->assertNotEmpty($errors);

    // Check error message.
    $error_messages = [];
    foreach ($errors as $line_errors) {
      foreach ($line_errors as $column_errors) {
        foreach ($column_errors as $error) {
          $error_messages[] = $error['message'];
        }
      }
    }

    $this->assertCount(1, $error_messages);
    $this->assertStringContainsString('Data provider method "providerTestData"', $error_messages[0]);
    $this->assertStringContainsString('should start with prefix "dataProvider"', $error_messages[0]);
    $this->assertStringContainsString('suggested name: "dataProviderTestData"', $error_messages[0]);
  }

  /**
   * Tests process method skips non-test classes.
   */
  public function testProcessSkipsNonTestClasses(): void {
    $code = <<<'PHP'
<?php
class MyClass {
  /**
   * @dataProvider providerData
   */
  public function doSomething($data) {}

  public function providerData() {
    return [['data']];
  }
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    // Should not have any errors because it's not a test class.
    $this->assertEmpty($errors);
  }

  /**
   * Tests process method with multiple invalid providers.
   */
  public function testProcessWithMultipleInvalidProviders(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider providerOne
   */
  public function testOne($data) {}

  /**
   * @dataProvider providerTwo
   */
  public function testTwo($data) {}

  public function providerOne() {
    return [['data']];
  }

  public function providerTwo() {
    return [['data']];
  }
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    // Should have two errors.
    $this->assertNotEmpty($errors);

    $error_messages = [];
    foreach ($errors as $line_errors) {
      foreach ($line_errors as $column_errors) {
        foreach ($column_errors as $error) {
          $error_messages[] = $error['message'];
        }
      }
    }

    $this->assertCount(2, $error_messages);
  }

  /**
   * Tests process method with method that is not a data provider.
   */
  public function testProcessSkipsNonDataProviderMethods(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  public function testSomething() {}

  public function helperMethod() {}

  public function providerNotUsed() {
    return [['data']];
  }
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    // Should not have any errors because methods are not data providers.
    $this->assertEmpty($errors);
  }

  /**
   * Tests isTestClass with function not in a class.
   */
  public function testIsTestClassWithFunctionNotInClass(): void {
    $code = <<<'PHP'
<?php
function globalFunction() {}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertFalse($result);
  }

  /**
   * Tests findDataProviders with other doc comment tags.
   */
  public function testFindDataProvidersWithOtherDocTags(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @param string $data
   * @return void
   * @group someGroup
   */
  public function testSomething($data) {}
}
PHP;

    $file = $this->processCode($code);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviders');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file);
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests findDataProviders with dataProvider annotation without method name.
   */
  public function testFindDataProvidersWithEmptyMethodName(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider
   */
  public function testSomething($data) {}
}
PHP;

    $file = $this->processCode($code);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviders');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file);
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests suggestName with method name that has only a common prefix.
   */
  public function testSuggestNameWithOnlyPrefix(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('suggestName');
    $method->setAccessible(TRUE);

    // Test with "provider" only - should keep it.
    $result = $method->invoke($this->sniff, 'provider');
    $this->assertEquals('dataProviderProvider', $result);

    // Test with "data" only - should keep it.
    $result = $method->invoke($this->sniff, 'data');
    $this->assertEquals('dataProviderData', $result);
  }

  /**
   * Tests process method with anonymous function/closure.
   */
  public function testProcessWithAnonymousFunction(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  public function testWithClosure() {
    $closure = function() {
      return true;
    };
  }
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    // Should not have errors for closures.
    $this->assertEmpty($errors);
  }

}
