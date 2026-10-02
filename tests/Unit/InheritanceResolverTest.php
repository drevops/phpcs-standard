<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Helpers\ClassLikeDeclaration;
use DrevOps\Helpers\InheritanceResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for InheritanceResolver.
 *
 * Methods under test are declared before their same-file ancestors, so the
 * first function with the given name is the one checked.
 */
#[CoversClass(InheritanceResolver::class)]
#[CoversClass(ClassLikeDeclaration::class)]
class InheritanceResolverTest extends UnitTestCase {

  /**
   * Namespace of the fixtures located through Composer.
   */
  protected const string FIXTURES_NAMESPACE = 'DrevOps\PhpcsStandard\Tests\Fixtures\Inheritance';

  /**
   * Test getting inherited parameter names with the Composer locator.
   *
   * @param string $code
   *   PHP code to test.
   * @param string $function_name
   *   Name of the method to check.
   * @param array<int, string>|null $expected
   *   Expected parameter names.
   */
  #[DataProvider('dataProviderGetInheritedParameterNames')]
  public function testGetInheritedParameterNames(string $code, string $function_name, ?array $expected): void {
    $file = $this->processCode($code);
    $resolver = new InheritanceResolver();

    $this->assertSame($expected, $resolver->getInheritedParameterNames($file, $this->findFunctionTokenByName($file, $function_name)));
  }

  /**
   * Data provider for testGetInheritedParameterNames.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderGetInheritedParameterNames(): array {
    $use_fixtures = 'use ' . self::FIXTURES_NAMESPACE . '\{UpstreamInterface, UpstreamParentClass, UpstreamTrait};';

    return [
      'global_function' => [
        '<?php function test($fooBar) {}',
        'test',
        [],
      ],
      'function_nested_in_method' => [
        '<?php class Test extends Base { public function outer() { function inner($fooBar) {} } } class Base { public function inner($fooBar) {} }',
        'inner',
        [],
      ],
      'class_without_ancestors' => [
        '<?php class Test { public function run($fooBar) {} }',
        'run',
        [],
      ],
      'private_method' => [
        '<?php class Test extends Base { private function run($fooBar) {} } class Base { public function run($fooBar) {} }',
        'run',
        [],
      ],
      'private_method_with_unresolved_parent' => [
        '<?php class Test extends Missing { private function run($fooBar) {} }',
        'run',
        [],
      ],
      'same_file_parent' => [
        '<?php class Test extends Base { public function run($fooBar, $extraParam) {} } class Base { public function run($fooBar) {} }',
        'run',
        ['$fooBar'],
      ],
      'same_file_parent_private_method' => [
        '<?php class Test extends Base { public function run($fooBar) {} } class Base { private function run($fooBar) {} }',
        'run',
        [],
      ],
      'same_file_parent_without_method' => [
        '<?php class Test extends Base { public function run($fooBar) {} } class Base { public function other($fooBar) {} }',
        'run',
        [],
      ],
      'same_file_interface_chain' => [
        '<?php interface Test extends Middle { public function run($fooBar); } interface Middle extends Root {} interface Root { public function run($fooBar, $bazQux); }',
        'run',
        ['$fooBar', '$bazQux'],
      ],
      'names_merged_from_several_ancestors' => [
        '<?php class Test extends Base implements Iface { public function run($fooBar, $bazQux) {} } class Base { public function run($fooBar) {} } interface Iface { public function run($bazQux, $fooBar); }',
        'run',
        ['$fooBar', '$bazQux'],
      ],
      'unresolved_parent' => [
        '<?php class Test extends Missing { public function run($fooBar) {} }',
        'run',
        NULL,
      ],
      'unresolved_parent_and_interface_declaring_method' => [
        '<?php class Test extends Missing implements Iface { public function run($fooBar, $extraParam) {} } interface Iface { public function run($fooBar); }',
        'run',
        ['$fooBar'],
      ],
      'unresolved_parent_and_interface_without_method' => [
        '<?php class Test extends Missing implements Iface { public function run($fooBar) {} } interface Iface {}',
        'run',
        NULL,
      ],
      'inheritance_cycle' => [
        '<?php class Test extends Base { public function run($fooBar) {} } class Base extends Test {}',
        'run',
        [],
      ],
      'self_reference' => [
        '<?php class Test extends Test { public function run($fooBar) {} }',
        'run',
        [],
      ],
      'method_name_case_insensitive' => [
        '<?php class Test extends Base { public function RUN($fooBar) {} } class Base { public function run($fooBar) {} }',
        'RUN',
        ['$fooBar'],
      ],
      'class_name_case_insensitive' => [
        '<?php class Test extends BASE { public function run($fooBar) {} } class Base { public function run($fooBar) {} }',
        'run',
        ['$fooBar'],
      ],
      'same_file_trait' => [
        '<?php class Test { use Helper; public function run($fooBar) {} } trait Helper { abstract public function run($fooBar); }',
        'run',
        ['$fooBar'],
      ],
      'enum_implementing_interface' => [
        '<?php enum Test: string implements Iface { case One = "one"; public function run($fooBar) {} } interface Iface { public function run($fooBar); }',
        'run',
        ['$fooBar'],
      ],
      'anonymous_class_extending_class' => [
        '<?php $object = new class extends Base { public function run($fooBar) {} }; class Base { public function run($fooBar) {} }',
        'run',
        ['$fooBar'],
      ],
      'internal_parent_constructor' => [
        '<?php class Test extends \RuntimeException { public function __construct($message = "", $code = 0, $previous = NULL, $extraParam = NULL) {} }',
        '__construct',
        ['$message', '$code', '$previous'],
      ],
      'internal_grandparent_constructor' => [
        '<?php class Test extends \LogicException { public function __construct($message = "") {} }',
        '__construct',
        ['$message', '$code', '$previous'],
      ],
      'internal_interface' => [
        '<?php class Test implements \ArrayAccess { public function offsetSet($offset, $value): void {} }',
        'offsetSet',
        ['$offset', '$value'],
      ],
      'internal_interface_without_method' => [
        '<?php class Test implements \Countable { public function add($fooBar) {} }',
        'add',
        [],
      ],
      'loaded_vendor_interface' => [
        '<?php class Test implements \PHP_CodeSniffer\Sniffs\Sniff { public function process($phpcsFile, $stackPtr) {} }',
        'process',
        ['$phpcsFile', '$stackPtr'],
      ],
      'located_parent_constructor' => [
        '<?php namespace App; ' . $use_fixtures . ' class Test extends UpstreamParentClass { public function __construct(array $constructorParam, $extraParam) {} }',
        '__construct',
        ['$constructorParam'],
      ],
      'located_grandparent_method' => [
        '<?php namespace App; ' . $use_fixtures . ' class Test extends UpstreamParentClass { public function grandparentMethod($grandparentParam) {} }',
        'grandparentMethod',
        ['$grandparentParam'],
      ],
      'located_protected_parent_method' => [
        '<?php namespace App; ' . $use_fixtures . ' class Test extends UpstreamParentClass { protected function protectedParentMethod($protectedParam) {} }',
        'protectedParentMethod',
        ['$protectedParam'],
      ],
      'located_private_parent_method' => [
        '<?php namespace App; ' . $use_fixtures . ' class Test extends UpstreamParentClass { public function privateParentMethod($privateParam) {} }',
        'privateParentMethod',
        [],
      ],
      'located_parent_method_case_insensitive' => [
        '<?php namespace App; ' . $use_fixtures . ' class Test extends UpstreamParentClass { public function caseinsensitivemethod($caseParam) {} }',
        'caseinsensitivemethod',
        ['$caseParam'],
      ],
      'located_parent_interface_method' => [
        '<?php namespace App; ' . $use_fixtures . ' class Test implements UpstreamInterface { public function parentInterfaceMethod($parentInterfaceParam) {} }',
        'parentInterfaceMethod',
        ['$parentInterfaceParam'],
      ],
      'located_nested_trait_method' => [
        '<?php namespace App; ' . $use_fixtures . ' class Test { use UpstreamTrait; public function nestedTraitMethod($nestedTraitParam) {} }',
        'nestedTraitMethod',
        ['$nestedTraitParam'],
      ],
      'located_ancestor_through_qualified_alias' => [
        '<?php namespace App; use DrevOps\PhpcsStandard\Tests\Fixtures as F; class Test extends F\Inheritance\UpstreamParentClass { public function parentMethod($parentParam) {} }',
        'parentMethod',
        ['$parentParam'],
      ],
      'located_ancestor_through_relative_name' => [
        '<?php namespace DrevOps\PhpcsStandard\Tests\Fixtures; class Test extends namespace\Inheritance\UpstreamParentClass { public function parentMethod($parentParam) {} }',
        'parentMethod',
        ['$parentParam'],
      ],
      'located_ancestor_without_method' => [
        '<?php namespace App; ' . $use_fixtures . ' class Test extends UpstreamParentClass { public function ownMethod($ownParam) {} }',
        'ownMethod',
        [],
      ],
      'missing_located_class' => [
        '<?php class Test extends \\' . self::FIXTURES_NAMESPACE . '\MissingClass { public function run($fooBar) {} }',
        'run',
        NULL,
      ],
    ];
  }

  /**
   * Test that closures are never inherited.
   */
  public function testClosureIsNotInherited(): void {
    $file = $this->processCode('<?php class Test extends Missing { public function run() { $closure = function ($fooBar) {}; } }');
    $closure_ptr = $file->findNext(T_CLOSURE, 0);
    $this->assertIsInt($closure_ptr);

    $this->assertSame([], (new InheritanceResolver())->getInheritedParameterNames($file, $closure_ptr));
  }

  /**
   * Test resolving ancestors with an injected source locator.
   *
   * @param array<string, string> $paths
   *   Source file paths keyed by class name, relative to the fixtures.
   * @param string $code
   *   PHP code to test.
   * @param string $function_name
   *   Name of the method to check.
   * @param array<int, string>|null $expected
   *   Expected parameter names.
   */
  #[DataProvider('dataProviderSourceLocator')]
  public function testSourceLocator(array $paths, string $code, string $function_name, ?array $expected): void {
    $fixtures = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR;
    $resolver = new InheritanceResolver(static fn(string $class_name): ?string => isset($paths[$class_name]) ? $fixtures . $paths[$class_name] : NULL);
    $file = $this->processCode($code);

    $this->assertSame($expected, $resolver->getInheritedParameterNames($file, $this->findFunctionTokenByName($file, $function_name)));
  }

  /**
   * Data provider for testSourceLocator.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderSourceLocator(): array {
    return [
      'missing_source_file' => [
        ['Base' => 'Inheritance' . DIRECTORY_SEPARATOR . 'Missing.php'],
        '<?php class Test extends Base { public function run($fooBar) {} }',
        'run',
        NULL,
      ],
      'source_file_without_class' => [
        ['Base' => 'Valid.php'],
        '<?php class Test extends Base { public function run($fooBar) {} }',
        'run',
        NULL,
      ],
      'several_classes_from_one_source_file' => [
        [
          'DrevOps\PhpcsStandard\Tests\Fixtures\InterfaceDefiningInheritedParams' => 'InheritedParameters.php',
          'DrevOps\PhpcsStandard\Tests\Fixtures\AbstractClassDefiningInheritedParam' => 'InheritedParameters.php',
        ],
        '<?php namespace DrevOps\PhpcsStandard\Tests\Fixtures; class Test extends AbstractClassDefiningInheritedParam implements InterfaceDefiningInheritedParams { public function methodWithInheritedParams($interfaceParamOne) {} public function methodWithInheritedParam($abstractParam) {} }',
        'methodWithInheritedParam',
        ['$abstractParam'],
      ],
    ];
  }

  /**
   * Test that each class is located once per resolver.
   */
  public function testLookupsAreCached(): void {
    $located = [];
    $resolver = new InheritanceResolver(static function (string $class_name) use (&$located): ?string {
      $located[] = $class_name;

      return NULL;
    });
    $file = $this->processCode('<?php class First extends Missing { public function one($fooBar) {} } class Second extends Missing { public function two($fooBar) {} }');

    $this->assertNull($resolver->getInheritedParameterNames($file, $this->findFunctionTokenByName($file, 'one')));
    $this->assertNull($resolver->getInheritedParameterNames($file, $this->findFunctionTokenByName($file, 'two')));
    $this->assertSame(['Missing'], $located);
  }

  /**
   * Test that a re-tokenized file is parsed again.
   *
   * The fixer re-tokenizes the same File object on every pass.
   */
  public function testRetokenizedFileIsParsedAgain(): void {
    $resolver = new InheritanceResolver();
    $file = $this->processCode('<?php class Test extends Base { public function run($fooBar) {} } class Base { public function run($fooBar) {} }');
    $this->assertSame(['$fooBar'], $resolver->getInheritedParameterNames($file, $this->findFunctionTokenByName($file, 'run')));

    $file->setContent('<?php class Test extends Base { public function run($fooBar) {} } class Base { public function run($bazQux) {} }');
    $file->parse();
    $file->fixer->loops++;

    $this->assertSame(['$bazQux'], $resolver->getInheritedParameterNames($file, $this->findFunctionTokenByName($file, 'run')));
  }

}
