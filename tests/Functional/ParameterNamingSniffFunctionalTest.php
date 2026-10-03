<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional integration test for ParameterNamingSniff.
 *
 * This tests the sniff by actually running phpcs as an external command,
 * which is the most reliable way to test PHPCS sniffs.
 */
#[CoversNothing]
class ParameterNamingSniffFunctionalTest extends FunctionalTestCase {

  /**
   * {@inheritdoc}
   */
  protected string $sniffSource = 'DrevOps.NamingConventions.ParameterNaming';

  #[Group('smoke')]
  public function testSmoke(): void {
    $this->runPhpcs(static::$fixtures . DIRECTORY_SEPARATOR . 'Valid.php');
  }

  public function testSniffDetectsParameterViolations(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'VariableNaming.php',
      [
        [
          'message' => 'Variable "$invalidParam" is not in snakeCase format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snakeCase format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snakeCase format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
  }

  /**
   * Test that only parameters declared by a same-file ancestor are exempt.
   */
  public function testInheritedParameters(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'InheritedParameters.php',
      [
        // Interface method declared by the interface itself.
        [
          'message' => 'Variable "$interfaceParamOne" is not in snakeCase format; try "$interface_param_one"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$interfaceParamTwo" is not in snakeCase format; try "$interface_param_two"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Method declared by the child interface itself.
        [
          'message' => 'Variable "$childInterfaceParam" is not in snakeCase format; try "$child_interface_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Abstract method declared by the abstract class itself.
        [
          'message' => 'Variable "$abstractParam" is not in snakeCase format; try "$abstract_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Concrete method following an abstract method.
        [
          'message' => 'Variable "$concreteParam" is not in snakeCase format; try "$concrete_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Method the implemented interface does not declare.
        [
          'message' => 'Variable "$ownParam" is not in snakeCase format; try "$own_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Parameter added to an overriding method.
        [
          'message' => 'Variable "$extraParam" is not in snakeCase format; try "$extra_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Parameter renamed in an overriding method.
        [
          'message' => 'Variable "$renamedParam" is not in snakeCase format; try "$renamed_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Private method in a class with an ancestor.
        [
          'message' => 'Variable "$privateParam" is not in snakeCase format; try "$private_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Class without ancestors.
        [
          'message' => 'Variable "$invalidNonInheritedParamOne" is not in snakeCase format; try "$invalid_non_inherited_param_one"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidNonInheritedParamTwo" is not in snakeCase format; try "$invalid_non_inherited_param_two"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
  }

  /**
   * Test that ancestors declared in other files are resolved.
   *
   * Covers ancestors located through Composer, an interface loaded by
   * PHP_CodeSniffer, internal classes, an enum, an anonymous class and a
   * parent that cannot be resolved.
   */
  public function testCrossFileInheritedParameters(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'InheritedParametersCrossFile.php',
      [
        // Method declared by the child interface itself.
        [
          'message' => 'Variable "$childInterfaceParam" is not in snakeCase format; try "$child_interface_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Parameter added to an inherited constructor.
        [
          'message' => 'Variable "$extraConstructorParam" is not in snakeCase format; try "$extra_constructor_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Method that is private in the parent.
        [
          'message' => 'Variable "$privateParam" is not in snakeCase format; try "$private_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Method no ancestor declares.
        [
          'message' => 'Variable "$ownParam" is not in snakeCase format; try "$own_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Private method.
        [
          'message' => 'Variable "$helperParam" is not in snakeCase format; try "$helper_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Method the PHP_CodeSniffer interface does not declare.
        [
          'message' => 'Variable "$phpcsFile" is not in snakeCase format; try "$phpcs_file"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Parameter added to an internal constructor.
        [
          'message' => 'Variable "$resourceName" is not in snakeCase format; try "$resource_name"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Method no internal ancestor declares.
        [
          'message' => 'Variable "$resourceName" is not in snakeCase format; try "$resource_name"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$itemName" is not in snakeCase format; try "$item_name"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Parameter added to a method found in a resolved ancestor.
        [
          'message' => 'Variable "$extraParam" is not in snakeCase format; try "$extra_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Private method in a class with an unresolved parent.
        [
          'message' => 'Variable "$helperParam" is not in snakeCase format; try "$helper_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Enum method no ancestor declares.
        [
          'message' => 'Variable "$enumParam" is not in snakeCase format; try "$enum_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Anonymous class method no ancestor declares.
        [
          'message' => 'Variable "$anonymousParam" is not in snakeCase format; try "$anonymous_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
  }

  /**
   * Test that classes of Drupal extensions are resolved as ancestors.
   *
   * The fixture extends a core module class, implements a contrib module
   * interface and extends a core module test class. Only the Drupal namespace
   * map locates them, as Composer does not autoload the fixture tree.
   *
   * @param string|null $drupal_root
   *   The drupalRoot property value, or NULL to leave it unset.
   * @param array<int, array<string, mixed>> $expected_violations
   *   Expected violations.
   */
  #[DataProvider('dataProviderDrupalExtensionAncestors')]
  public function testDrupalExtensionAncestors(?string $drupal_root, array $expected_violations): void {
    $this->runPhpcs(static::$fixtures . DIRECTORY_SEPARATOR . 'Drupal/modules/custom/my_module/src/InheritedParameters.php', $expected_violations, $this->createRuleset($drupal_root));
  }

  /**
   * Data provider for testDrupalExtensionAncestors.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderDrupalExtensionAncestors(): array {
    $violation = static fn(string $name, string $suggestion): array => [
      'message' => sprintf('Variable "$%s" is not in snakeCase format; try "$%s"', $name, $suggestion),
      'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
      'fixable' => TRUE,
    ];

    $resolved = [
      // Renamed parameter of a core module class method.
      $violation('resultRow', 'result_row'),
      // Method no ancestor declares.
      $violation('labelText', 'label_text'),
      // Parameter added to a contrib module interface method.
      $violation('replaceOptions', 'replace_options'),
      // Renamed parameter of a core module test class method.
      $violation('importTestViews', 'import_test_views'),
      // Class without ancestors.
      $violation('rawValue', 'raw_value'),
    ];

    $unresolved = [
      // Class without ancestors.
      $violation('rawValue', 'raw_value'),
    ];

    return [
      'absolute_path' => [dirname(__DIR__) . '/Fixtures/Drupal', $resolved],
      'relative_path' => ['tests/Fixtures/Drupal', $resolved],
      'false' => ['false', $unresolved],
      'not_set_without_drupal_core' => [NULL, $unresolved],
    ];
  }

  /**
   * Test that a drupalRoot without Drupal core is reported as an error.
   */
  public function testInvalidDrupalRoot(): void {
    $this->sniffSource = 'Internal.Exception';

    $violations = $this->runPhpcsJson(static::$fixtures . DIRECTORY_SEPARATOR . 'Drupal/modules/custom/my_module/src/InheritedParameters.php', $this->createRuleset('tests/Fixtures'));

    $this->assertCount(1, $violations);
    $message = $violations[0]['message'] ?? NULL;
    $this->assertIsString($message);
    $this->assertStringContainsString('Invalid drupalRoot "tests/Fixtures": core/lib/Drupal.php not found.', $message);
  }

  /**
   * Test that parameters of nested functions are flagged on the signature.
   *
   * Covers closures, arrow functions and anonymous class methods, including
   * static, by-reference and nested ones and those passed as call arguments.
   * Uses in bodies, 'use' clauses and arrow function captures are not flagged.
   */
  public function testNestedFunctionParameters(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'NestedFunctionParameters.php',
      [
        // Closure.
        [
          'message' => 'Variable "$closureParam" is not in snakeCase format; try "$closure_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Arrow function.
        [
          'message' => 'Variable "$arrowParam" is not in snakeCase format; try "$arrow_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Static closure and static arrow function.
        [
          'message' => 'Variable "$staticClosureParam" is not in snakeCase format; try "$static_closure_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$staticArrowParam" is not in snakeCase format; try "$static_arrow_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // By-reference arrow function.
        [
          'message' => 'Variable "$referenceParam" is not in snakeCase format; try "$reference_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Closure and arrow function passed as call arguments.
        [
          'message' => 'Variable "$mapItem" is not in snakeCase format; try "$map_item"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$filterItem" is not in snakeCase format; try "$filter_item"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Method parameters imported with 'use' or captured by an arrow
        // function.
        [
          'message' => 'Variable "$importedParam" is not in snakeCase format; try "$imported_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$referenceImportedParam" is not in snakeCase format; try "$reference_imported_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$capturedParam" is not in snakeCase format; try "$captured_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Nested arrow functions.
        [
          'message' => 'Variable "$outerArrowParam" is not in snakeCase format; try "$outer_arrow_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$middleArrowParam" is not in snakeCase format; try "$middle_arrow_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$innerArrowParam" is not in snakeCase format; try "$inner_arrow_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Closure inside an arrow function inside a method.
        [
          'message' => 'Variable "$mixedMethodParam" is not in snakeCase format; try "$mixed_method_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$mixedArrowParam" is not in snakeCase format; try "$mixed_arrow_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$mixedClosureParam" is not in snakeCase format; try "$mixed_closure_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$mixedInnerParam" is not in snakeCase format; try "$mixed_inner_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Method parameter and the closure parameter shadowing it.
        [
          'message' => 'Variable "$shadowedParam" is not in snakeCase format; try "$shadowed_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$shadowedParam" is not in snakeCase format; try "$shadowed_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Match expression in an arrow function.
        [
          'message' => 'Variable "$matchSubject" is not in snakeCase format; try "$match_subject"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$matchDefault" is not in snakeCase format; try "$match_default"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Method parameter a closure does not import.
        [
          'message' => 'Variable "$notImportedParam" is not in snakeCase format; try "$not_imported_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Function returning an anonymous class, and the class method.
        [
          'message' => 'Variable "$factoryParam" is not in snakeCase format; try "$factory_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$anonymousParam" is not in snakeCase format; try "$anonymous_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Plain function.
        [
          'message' => 'Variable "$plainParam" is not in snakeCase format; try "$plain_param"',
          'source' => 'DrevOps.NamingConventions.ParameterNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
  }

  /**
   * Test that phpcbf renames nested function parameters consistently.
   *
   * Runs both naming sniffs, as the signatures are renamed by ParameterNaming
   * and the remaining uses by LocalVariableNaming on the next fixer loop.
   */
  public function testPhpcbfRenamesNestedFunctionParameters(): void {
    $fixed = $this->runPhpcbf(
      static::$fixtures . DIRECTORY_SEPARATOR . 'NestedFunctionParameters.php',
      'DrevOps.NamingConventions.LocalVariableNaming,DrevOps.NamingConventions.ParameterNaming'
    );

    // Every variable in the fixture is fixable, so a remaining camelCase name
    // is a use that was not renamed with its declaration.
    $this->assertDoesNotMatchRegularExpression('/\$[a-z][a-z0-9]*[A-Z]/', $fixed);

    $this->assertStringContainsString('return function ($closure_param) {', $fixed);
    $this->assertStringContainsString('return $closure_param;', $fixed);
    $this->assertStringContainsString('return fn($arrow_param) => $arrow_param * 2;', $fixed);
    $this->assertStringContainsString('return fn&(array &$reference_param) => $reference_param;', $fixed);
    $this->assertStringContainsString('public function methodWithUse($imported_param): \Closure {', $fixed);
    $this->assertStringContainsString('return function () use ($imported_param) {', $fixed);
    $this->assertStringContainsString('return $imported_param;', $fixed);
    $this->assertStringContainsString('return fn($item_value) => $item_value . $captured_param;', $fixed);
    $this->assertStringContainsString('fn($middle_arrow_param) => fn($inner_arrow_param) => $outer_arrow_param + $middle_arrow_param + $inner_arrow_param;', $fixed);
    $this->assertStringContainsString('fn($mixed_arrow_param) => function ($mixed_closure_param) use ($mixed_arrow_param, $mixed_method_param) {', $fixed);
    $this->assertStringContainsString('public function anonymousMethod($anonymous_param) {', $fixed);
    $this->assertStringContainsString('return $anonymous_param;', $fixed);
  }

  /**
   * Test that properties are not flagged (only parameters).
   */
  public function testPropertiesAreNotFlagged(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'AttributedProperties.php',
      []
    );
  }

  /**
   * Test that phpcbf fixes both parameter names and docblock @param tags.
   */
  public function testPhpcbfFixesDocblockParams(): void {
    $fixture_file = static::$fixtures . DIRECTORY_SEPARATOR . 'ParameterDocblockMismatch.php';
    $this->assertFileExists($fixture_file, 'Fixture file must exist');

    // Create a temporary copy to fix.
    $temp_file = static::$tmp . DIRECTORY_SEPARATOR . 'test_' . uniqid() . '.php';
    copy($fixture_file, $temp_file);

    // Run phpcbf to fix the file.
    $phpcbf_bin = __DIR__ . '/../../vendor/bin/phpcbf';
    $this->assertFileExists($phpcbf_bin, 'PHPCBF binary must exist');

    $this->processRun(
      $phpcbf_bin,
      ['--standard=DrevOps', '--sniffs=DrevOps.NamingConventions.ParameterNaming', '-q', $temp_file],
      timeout: 120
    );

    // Read the fixed file.
    $fixed_content = file_get_contents($temp_file);
    $this->assertIsString($fixed_content, 'Fixed file should be readable');

    // Verify that parameter names in signatures are fixed.
    $this->assertStringContainsString('function methodWithDocblock(string $invalid_param', $fixed_content, 'Parameter signature should be fixed');
    $this->assertStringContainsString('int $another_invalid', $fixed_content, 'Second parameter signature should be fixed');

    // Verify that parameter names in docblocks are also fixed.
    $this->assertStringContainsString('@param string $invalid_param', $fixed_content, 'Docblock @param should be fixed');
    $this->assertStringContainsString('@param int $another_invalid', $fixed_content, 'Docblock @param should be fixed for second parameter');

    // Verify old parameter names are gone from signatures and docblocks.
    // Note: Parameter usages in method bodies are NOT fixed by this sniff -
    // that's the job of LocalVariableNamingSniff.
    $this->assertStringNotContainsString('function methodWithDocblock(string $invalidParam', $fixed_content, 'Old parameter name should not exist in signature');
    $this->assertStringNotContainsString('@param string $invalidParam', $fixed_content, 'Old parameter name should not exist in docblock');
    $this->assertStringNotContainsString('@param int $anotherInvalid', $fixed_content, 'Old parameter name should not exist in docblock');

    // Verify complex types are preserved.
    $this->assertStringContainsString('array<string, mixed> $invalid_param', $fixed_content, 'Complex array type should be preserved');
    $this->assertStringContainsString('\DateTime|null $optional_invalid', $fixed_content, 'Union type should be preserved');

    // Verify multiline descriptions are preserved.
    $this->assertStringContainsString('This is a parameter with a very long description', $fixed_content, 'Multiline descriptions should be preserved');
  }

  /**
   * Create a ruleset that sets the drupalRoot property of the standard.
   *
   * @param string|null $drupal_root
   *   The property value, or NULL to leave it unset.
   *
   * @return string
   *   The ruleset path.
   */
  protected function createRuleset(?string $drupal_root): string {
    $properties = $drupal_root === NULL ? '' : sprintf('<properties><property name="drupalRoot" value="%s"/></properties>', htmlspecialchars($drupal_root, ENT_XML1 | ENT_QUOTES));
    $path = static::$tmp . DIRECTORY_SEPARATOR . 'phpcs.xml';

    file_put_contents($path, sprintf('<?xml version="1.0"?><ruleset name="Test"><rule ref="DrevOps">%s</rule></ruleset>', $properties));

    return $path;
  }

}
