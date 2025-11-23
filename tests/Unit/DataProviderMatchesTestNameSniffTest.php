<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Sniffs\TestingPractices\DataProviderMatchesTestNameSniff;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for DataProviderMatchesTestNameSniff.
 */
#[CoversClass(DataProviderMatchesTestNameSniff::class)]
class DataProviderMatchesTestNameSniffTest extends UnitTestCase {

  /**
   * The sniff instance.
   */
  private DataProviderMatchesTestNameSniff $sniff;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Configure to run only DataProviderMatchesTestName sniff.
    $this->config->sniffs = ['DrevOps.TestingPractices.DataProviderMatchesTestName'];
    $this->ruleset = new Ruleset($this->config);
    $this->sniff = new DataProviderMatchesTestNameSniff();
  }

  /**
   * Tests the register method.
   */
  public function testRegister(): void {
    $result = $this->sniff->register();
    $this->assertEquals([T_FUNCTION], $result);
  }

  /**
   * Tests isTestMethod with valid test method.
   */
  public function testIsTestMethodWithValidTest(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestMethod');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'testUserLogin');
    $this->assertTrue($result);
  }

  /**
   * Tests isTestMethod with invalid methods.
   */
  public function testIsTestMethodWithInvalidMethods(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestMethod');
    $method->setAccessible(TRUE);

    // Not starting with test.
    $this->assertFalse($method->invoke($this->sniff, 'userLogin'));

    // Test not followed by uppercase.
    $this->assertFalse($method->invoke($this->sniff, 'test_user_login'));

    // Helper method.
    $this->assertFalse($method->invoke($this->sniff, 'helperMethod'));
  }

  /**
   * Tests extractTestName method.
   */
  public function testExtractTestName(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('extractTestName');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, 'testUserLogin');
    $this->assertEquals('UserLogin', $result);

    $result = $method->invoke($this->sniff, 'testEmailValidation');
    $this->assertEquals('EmailValidation', $result);
  }

  /**
   * Tests providerMatchesTest with exact matches.
   */
  public function testProviderMatchesTestWithExactMatch(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('providerMatchesTest');
    $method->setAccessible(TRUE);

    // Exact match.
    $this->assertTrue($method->invoke($this->sniff, 'dataProviderUserLogin', 'UserLogin'));

    // Different prefix.
    $this->assertTrue($method->invoke($this->sniff, 'providerUserLogin', 'UserLogin'));

    // No prefix.
    $this->assertTrue($method->invoke($this->sniff, 'UserLogin', 'UserLogin'));
  }

  /**
   * Tests providerMatchesTest with non-matches.
   */
  public function testProviderMatchesTestWithNonMatch(): void {
    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('providerMatchesTest');
    $method->setAccessible(TRUE);

    // Partial match.
    $this->assertFalse($method->invoke($this->sniff, 'dataProviderLogin', 'UserLogin'));

    // With suffix.
    $this->assertFalse($method->invoke($this->sniff, 'dataProviderUserLoginCases', 'UserLogin'));

    // Completely different.
    $this->assertFalse($method->invoke($this->sniff, 'providerAuth', 'UserLogin'));
  }

  /**
   * Tests findDataProviderAnnotation with valid annotation.
   */
  public function testFindDataProviderAnnotationWithValid(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider dataProviderUserLogin
   */
  public function testUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('findDataProviderAnnotation');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertEquals('dataProviderUserLogin', $result);
  }

  /**
   * Tests findDataProviderAnnotation with external provider.
   */
  public function testFindDataProviderAnnotationWithExternal(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider ExternalClass::providerData
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
   * Tests findDataProviderAnnotation with no annotation.
   */
  public function testFindDataProviderAnnotationWithNone(): void {
    $code = <<<'PHP'
<?php
class MyTest {
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
   * Tests process method with matching provider.
   */
  public function testProcessWithMatchingProvider(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider dataProviderUserLogin
   */
  public function testUserLogin() {}

  public function dataProviderUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    $this->assertEmpty($errors);
  }

  /**
   * Tests process method with non-matching provider.
   */
  public function testProcessWithNonMatchingProvider(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider dataProviderLogin
   */
  public function testUserLogin() {}

  public function dataProviderLogin() {}
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
    $this->assertStringContainsString('Data provider method "dataProviderLogin"', $error_messages[0]);
    $this->assertStringContainsString('does not match test method "testUserLogin"', $error_messages[0]);
    $this->assertStringContainsString('Expected provider name to end with "UserLogin"', $error_messages[0]);
  }

  /**
   * Tests process method skips non-test methods.
   */
  public function testProcessSkipsNonTestMethods(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  /**
   * @dataProvider someProvider
   */
  public function helperMethod() {}
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    $this->assertEmpty($errors);
  }

  /**
   * Tests process method with external provider.
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

    $this->assertEmpty($errors);
  }

  /**
   * Tests isTestClass with function not in class.
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
   * Tests isTestClass with class extending TestCase.
   */
  public function testIsTestClassWithExtendsTestCase(): void {
    $code = <<<'PHP'
<?php
class MyTest extends TestCase {
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
    $function_ptr = $this->findFunctionToken($file);

    $reflection = new \ReflectionClass($this->sniff);
    $method = $reflection->getMethod('isTestClass');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertFalse($result);
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
   * Tests findDataProviderAttribute with valid attribute.
   */
  public function testFindDataProviderAttributeWithValid(): void {
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

    $result = $method->invoke($this->sniff, $file, $function_ptr);
    $this->assertEquals('dataProviderUserLogin', $result);
  }

  /**
   * Tests findDataProviderAttribute with no attribute.
   */
  public function testFindDataProviderAttributeWithNone(): void {
    $code = <<<'PHP'
<?php
class MyTest {
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

  /**
   * Tests process method with attribute-based provider.
   */
  public function testProcessWithAttributeProvider(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  #[DataProvider('dataProviderUserLogin')]
  public function testUserLogin() {}

  public function dataProviderUserLogin() {}
}
PHP;

    $file = $this->processCode($code);
    $errors = $file->getErrors();

    $this->assertEmpty($errors);
  }

  /**
   * Tests process method with non-matching attribute provider.
   */
  public function testProcessWithNonMatchingAttributeProvider(): void {
    $code = <<<'PHP'
<?php
class MyTest {
  #[DataProvider('dataProviderLogin')]
  public function testUserLogin() {}

  public function dataProviderLogin() {}
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
    $this->assertStringContainsString('Data provider method "dataProviderLogin"', $error_messages[0]);
    $this->assertStringContainsString('does not match test method "testUserLogin"', $error_messages[0]);
  }

}
