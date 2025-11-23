<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\TestingPractices\DataProviderOrderSniff;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for DataProviderOrderSniff.
 */
#[CoversClass(DataProviderOrderSniff::class)]
class DataProviderOrderSniffTest extends UnitTestCase {

  /**
   * The sniff instance.
   */
  private DataProviderOrderSniff $sniff;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure to run only DataProviderOrder sniff.
    $this->config->sniffs = ['DrevOps.TestingPractices.DataProviderOrder'];
    $this->ruleset = new Ruleset($this->config);
    $this->sniff = new DataProviderOrderSniff();
  }

  /**
   * Tests the register method.
   */
  public function testRegister(): void {
    $result = $this->sniff->register();
    $this->assertEquals([T_CLASS], $result);
  }

  /**
   * Tests process with correct order.
   */
  public function testProcessWithCorrectOrder(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider dataProviderUserLogin
   */
  public function testUserLogin() {}

  public function dataProviderUserLogin() {
    return [];
  }
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    $this->assertEmpty($errors);
  }

  /**
   * Tests process with incorrect order.
   */
  public function testProcessWithIncorrectOrder(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  public function dataProviderUserLogin() {
    return [];
  }

  /**
   * @dataProvider dataProviderUserLogin
   */
  public function testUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    $this->assertNotEmpty($errors);

    $error_messages = [];
    foreach ($errors as $line_errors) {
      foreach ($line_errors as $column_errors) {
        foreach ($column_errors as $error) {
          $error_messages[] = $error['message'];
        }
      }
    }

    $this->assertCount(1, $error_messages);
    $this->assertStringContainsString('Data provider method "dataProviderUserLogin"', $error_messages[0]);
    $this->assertStringContainsString('appears before test method "testUserLogin"', $error_messages[0]);
  }

  /**
   * Tests process with helper method between test and provider.
   */
  public function testProcessWithHelperBetweenTestAndProvider(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider dataProviderUserLogin
   */
  public function testUserLogin() {}

  private function helperMethod() {}

  public function dataProviderUserLogin() {
    return [];
  }
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    // Helper between test and provider is OK.
    $this->assertEmpty($errors);
  }

  /**
   * Tests process with shared provider used by multiple tests.
   */
  public function testProcessWithSharedProvider(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider dataProviderShared
   */
  public function testFirst() {}

  /**
   * @dataProvider dataProviderShared
   */
  public function testSecond() {}

  public function dataProviderShared() {
    return [];
  }
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    // Shared provider after first test is OK.
    $this->assertEmpty($errors);
  }

  /**
   * Tests process with external provider.
   */
  public function testProcessWithExternalProvider(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider ExternalClass::providerData
   */
  public function testUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    // External providers are skipped.
    $this->assertEmpty($errors);
  }

  /**
   * Tests process with PHP 8 attribute.
   */
  public function testProcessWithAttribute(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  public function dataProviderUserLogin() {
    return [];
  }

  #[DataProvider('dataProviderUserLogin')]
  public function testUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    $this->assertNotEmpty($errors);
  }

  /**
   * Tests process skips non-test classes.
   */
  public function testProcessSkipsNonTestClasses(): void {
    $code = <<<'PHP'
<?php
class MyClass {
  public function dataProviderSomething() {}

  /**
   * @dataProvider dataProviderSomething
   */
  public function doSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    $this->assertEmpty($errors);
  }

  /**
   * Tests isTestClass with test class.
   */
  public function testIsTestClassWithTestClass(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  public function testSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $class_ptr = $this->findClassToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $class_ptr);
    $this->assertTrue($result);
  }

  /**
   * Tests isTestClass with non-test class.
   */
  public function testIsTestClassWithNonTestClass(): void {
    $code = <<<'PHP'
<?php
class MyClass {
  public function doSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $class_ptr = $this->findClassToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $class_ptr);
    $this->assertFalse($result);
  }

  /**
   * Tests isTestClass with class extending TestCase.
   */
  public function testIsTestClassWithExtendsTestCase(): void {
    $code = <<<'PHP'
<?php
class MyFeature extends TestCase {
  public function testSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $class_ptr = $this->findClassToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $class_ptr);
    $this->assertTrue($result);
  }

  /**
   * Tests isTestClass with class extending non-TestCase.
   */
  public function testIsTestClassWithExtendsNonTestCase(): void {
    $code = <<<'PHP'
<?php
class MyClass extends BaseClass {
  public function testSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $class_ptr = $this->findClassToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $class_ptr);
    $this->assertFalse($result);
  }

  /**
   * Tests providerPosition property default value.
   */
  public function testProviderPositionDefaultValue(): void {
    $this->assertEquals('after', $this->sniff->providerPosition);
  }

  /**
   * Tests providerPosition property can be changed.
   */
  public function testProviderPositionCanBeChanged(): void {
    $this->sniff->providerPosition = 'before';
    $this->assertEquals('before', $this->sniff->providerPosition);
  }

  /**
   * Tests findDataProviderAnnotation with no comment opener.
   */
  public function testFindDataProviderAnnotationWithNoCommentOpener(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /** @dataProvider dataProviderTest */
  public function testSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviderAnnotation');
    $method->setAccessible(TRUE);

    // This should still work with single-line doc comments.
    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertEquals('dataProviderTest', $result);
  }

  /**
   * Tests findDataProviderAnnotation with different tag.
   */
  public function testFindDataProviderAnnotationWithDifferentTag(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @param string $value
   * @return void
   */
  public function testSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviderAnnotation');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertNull($result);
  }

  /**
   * Tests findDataProviderAnnotation with empty tag.
   */
  public function testFindDataProviderAnnotationWithEmptyTag(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider
   */
  public function testSomething() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviderAnnotation');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertNull($result);
  }

  /**
   * Tests findDataProviderAttribute with distant attribute.
   */
  public function testFindDataProviderAttributeWithDistantAttribute(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  #[DataProvider('dataProviderUserLogin')]





  public function testUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviderAttribute');
    $method->setAccessible(TRUE);

    // Should return NULL if attribute is too far (>10 tokens).
    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertNull($result);
  }

  /**
   * Tests findDataProviderAttribute with wrong attribute name.
   */
  public function testFindDataProviderAttributeWithWrongName(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  #[Test]
  public function testUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviderAttribute');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertNull($result);
  }

  /**
   * Tests findDataProviderAttribute with no parenthesis.
   */
  public function testFindDataProviderAttributeWithNoParenthesis(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  #[DataProvider]
  public function testUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviderAttribute');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertNull($result);
  }

  /**
   * Tests findDataProviderAttribute with no string argument.
   */
  public function testFindDataProviderAttributeWithNoString(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  #[DataProvider()]
  public function testUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviderAttribute');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertNull($result);
  }

  /**
   * Tests findDataProviderAttribute with external provider.
   */
  public function testFindDataProviderAttributeWithExternal(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  #[DataProvider('ExternalClass::providerData')]
  public function testUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviderAttribute');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertNull($result);
  }

}
