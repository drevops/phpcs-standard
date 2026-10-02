<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Helpers\ClassLikeDeclaration;
use DrevOps\Helpers\ClassLikeParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for ClassLikeParser.
 */
#[CoversClass(ClassLikeParser::class)]
#[CoversClass(ClassLikeDeclaration::class)]
class ClassLikeParserTest extends UnitTestCase {

  /**
   * Test parsing class-like declarations.
   *
   * @param string $code
   *   PHP code to parse.
   * @param array<int, array<string, mixed>> $expected
   *   Expected declarations in token order.
   */
  #[DataProvider('dataProviderParse')]
  public function testParse(string $code, array $expected): void {
    $file = $this->processCode($code);
    $parser = new ClassLikeParser();

    $actual = [];
    foreach ($parser->parse($file) as $declaration) {
      $actual[] = ['name' => $declaration->name, 'ancestors' => $declaration->ancestors, 'methods' => $declaration->methods];
    }

    $this->assertSame($expected, $actual);
  }

  /**
   * Data provider for testParse.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderParse(): array {
    return [
      'class_without_namespace' => [
        '<?php class Foo extends Bar implements Baz { public function run($fooBar, $baz) {} }',
        [
          ['name' => 'Foo', 'ancestors' => ['Bar', 'Baz'], 'methods' => ['run' => ['$fooBar', '$baz']]],
        ],
      ],
      'class_in_namespace' => [
        '<?php namespace App\Sub; class Foo extends Bar {}',
        [
          ['name' => 'App\Sub\Foo', 'ancestors' => ['App\Sub\Bar'], 'methods' => []],
        ],
      ],
      'imports_with_alias_group_and_leading_backslash' => [
        '<?php namespace App; use Vendor\Pkg\Base; use Vendor\Pkg\{Iface as Alias, Other\Deep}; use \Lead\Slash; class Foo extends Base implements Alias, Deep, Slash, Deep\Thing {}',
        [
          ['name' => 'App\Foo', 'ancestors' => ['Vendor\Pkg\Base', 'Vendor\Pkg\Iface', 'Vendor\Pkg\Other\Deep', 'Lead\Slash', 'Vendor\Pkg\Other\Deep\Thing'], 'methods' => []],
        ],
      ],
      'multiple_imports_in_one_statement' => [
        '<?php use A\B, C\D as E; class Foo implements B, E {}',
        [
          ['name' => 'Foo', 'ancestors' => ['A\B', 'C\D'], 'methods' => []],
        ],
      ],
      'function_and_const_imports_skipped' => [
        '<?php namespace App; use function Vendor\helper; use const Vendor\VALUE; use Vendor\{function first, const SECOND, Klass}; class Foo extends Klass implements helper, VALUE, first, SECOND {}',
        [
          ['name' => 'App\Foo', 'ancestors' => ['Vendor\Klass', 'App\helper', 'App\VALUE', 'App\first', 'App\SECOND'], 'methods' => []],
        ],
      ],
      'import_with_keyword_namespace_segment' => [
        '<?php use Function\Base; class Foo extends Base {}',
        [
          ['name' => 'Foo', 'ancestors' => ['Function\Base'], 'methods' => []],
        ],
      ],
      'fully_qualified_and_relative_names' => [
        '<?php namespace App; class Foo extends \Root\Base implements namespace\Local, Sub\Name {}',
        [
          ['name' => 'App\Foo', 'ancestors' => ['Root\Base', 'App\Local', 'App\Sub\Name'], 'methods' => []],
        ],
      ],
      'qualified_name_through_alias' => [
        '<?php namespace App; use Vendor\Pkg; class Foo extends Pkg\Base {}',
        [
          ['name' => 'App\Foo', 'ancestors' => ['Vendor\Pkg\Base'], 'methods' => []],
        ],
      ],
      'alias_matched_case_insensitively' => [
        '<?php use Vendor\Base; class Foo extends BASE {}',
        [
          ['name' => 'Foo', 'ancestors' => ['Vendor\Base'], 'methods' => []],
        ],
      ],
      'braced_namespaces_reset_imports' => [
        '<?php namespace App { use Vendor\Base; class Foo extends Base {} } namespace { class Bar extends Base {} }',
        [
          ['name' => 'App\Foo', 'ancestors' => ['Vendor\Base'], 'methods' => []],
          ['name' => 'Bar', 'ancestors' => ['Base'], 'methods' => []],
        ],
      ],
      'unbraced_namespaces_reset_imports' => [
        '<?php namespace First; use Vendor\Base; class Foo extends Base {} namespace Second; class Bar extends Base {}',
        [
          ['name' => 'First\Foo', 'ancestors' => ['Vendor\Base'], 'methods' => []],
          ['name' => 'Second\Bar', 'ancestors' => ['Second\Base'], 'methods' => []],
        ],
      ],
      'relative_function_call_keeps_namespace' => [
        '<?php namespace App; use Vendor\Base; namespace\helper(); class Foo extends Base {}',
        [
          ['name' => 'App\Foo', 'ancestors' => ['Vendor\Base'], 'methods' => []],
        ],
      ],
      'import_after_declaration_ignored' => [
        '<?php namespace App; class Foo extends Base {} use Vendor\Base;',
        [
          ['name' => 'App\Foo', 'ancestors' => ['App\Base'], 'methods' => []],
        ],
      ],
      'closure_use_is_not_an_import' => [
        '<?php $value = 1; $closure = function () use ($value) { return Vendor\Base::class; }; class Foo extends Baseclass {}',
        [
          ['name' => 'Foo', 'ancestors' => ['Baseclass'], 'methods' => []],
        ],
      ],
      'unterminated_import_ignored' => [
        '<?php class Foo extends Base {} use Vendor\Base',
        [
          ['name' => 'Foo', 'ancestors' => ['Base'], 'methods' => []],
        ],
      ],
      'unterminated_namespace' => [
        '<?php namespace App',
        [],
      ],
      'interface_extending_interfaces' => [
        '<?php interface Foo extends Bar, \Baz { public function run(int $count); }',
        [
          ['name' => 'Foo', 'ancestors' => ['Bar', 'Baz'], 'methods' => ['run' => ['$count']]],
        ],
      ],
      'interface_without_name' => [
        '<?php interface {}',
        [
          ['name' => NULL, 'ancestors' => [], 'methods' => []],
        ],
      ],
      'trait_uses_with_conflict_block' => [
        '<?php class Foo { use TraitOne, \Fq\TraitTwo { TraitOne::run insteadof TraitTwo; TraitTwo::run as protected walk; } use TraitThree; }',
        [
          ['name' => 'Foo', 'ancestors' => ['TraitOne', 'Fq\TraitTwo', 'TraitThree'], 'methods' => []],
        ],
      ],
      'trait_using_trait' => [
        '<?php trait Foo { use Bar; abstract public function run($fooBar); }',
        [
          ['name' => 'Foo', 'ancestors' => ['Bar'], 'methods' => ['run' => ['$fooBar']]],
        ],
      ],
      'enum_with_interface_and_trait' => [
        '<?php enum Suit: string implements HasLabel { use LabelTrait; case Hearts = "H"; public function label(string $localeCode): string { return ""; } }',
        [
          ['name' => 'Suit', 'ancestors' => ['HasLabel', 'LabelTrait'], 'methods' => ['label' => ['$localeCode']]],
        ],
      ],
      'anonymous_class_with_anonymous_class_argument' => [
        '<?php $object = new class(new class extends Inner {}) extends Outer implements Iface { public function run($value) {} };',
        [
          ['name' => NULL, 'ancestors' => ['Outer', 'Iface'], 'methods' => ['run' => ['$value']]],
          ['name' => NULL, 'ancestors' => ['Inner'], 'methods' => []],
        ],
      ],
      'private_methods_skipped_and_names_lowercased' => [
        '<?php abstract class Foo { private function hidden($a) {} protected function Visible($b) {} abstract public function abstractOne($c); public static function staticOne(...$d) {} }',
        [
          ['name' => 'Foo', 'ancestors' => [], 'methods' => ['visible' => ['$b'], 'abstractone' => ['$c'], 'staticone' => ['$d']]],
        ],
      ],
      'methods_of_nested_class_not_members' => [
        '<?php class Outer { public function make() { return new class { public function inner($x) { $closure = function ($y) {}; } }; } }',
        [
          ['name' => 'Outer', 'ancestors' => [], 'methods' => ['make' => []]],
          ['name' => NULL, 'ancestors' => [], 'methods' => ['inner' => ['$x']]],
        ],
      ],
    ];
  }

  /**
   * Test resolving class names.
   *
   * @param string $name
   *   The class name as written.
   * @param string $namespace
   *   The namespace in effect.
   * @param array<string, string> $imports
   *   Fully qualified names keyed by lowercase alias.
   * @param string $expected
   *   The expected fully qualified name.
   */
  #[DataProvider('dataProviderResolveName')]
  public function testResolveName(string $name, string $namespace, array $imports, string $expected): void {
    $parser = new ClassLikeParser();
    $method = (new \ReflectionClass($parser))->getMethod('resolveName');

    $this->assertSame($expected, $method->invoke($parser, $name, $namespace, $imports));
  }

  /**
   * Data provider for testResolveName.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderResolveName(): array {
    return [
      'fully_qualified' => ['\Vendor\Base', 'App', ['base' => 'Other\Base'], 'Vendor\Base'],
      'relative' => ['namespace\Sub\Base', 'App', [], 'App\Sub\Base'],
      'relative_uppercase_keyword' => ['NAMESPACE\Base', 'App', [], 'App\Base'],
      'relative_in_global_namespace' => ['namespace\Base', '', [], 'Base'],
      'unqualified_imported' => ['Base', 'App', ['base' => 'Vendor\Base'], 'Vendor\Base'],
      'unqualified_not_imported' => ['Base', 'App', [], 'App\Base'],
      'unqualified_in_global_namespace' => ['Base', '', [], 'Base'],
      'qualified_through_alias' => ['Pkg\Base', 'App', ['pkg' => 'Vendor\Pkg'], 'Vendor\Pkg\Base'],
      'qualified_without_alias' => ['Pkg\Base', 'App', ['base' => 'Vendor\Base'], 'App\Pkg\Base'],
      'alias_case_insensitive' => ['BASE', 'App', ['base' => 'Vendor\Base'], 'Vendor\Base'],
    ];
  }

}
