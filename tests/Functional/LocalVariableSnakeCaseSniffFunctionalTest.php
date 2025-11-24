<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional integration test for LocalVariableSnakeCaseSniff.
 *
 * This tests the sniff by actually running phpcs as an external command,
 * which is the most reliable way to test PHPCS sniffs.
 */
#[CoversNothing]
class LocalVariableSnakeCaseSniffFunctionalTest extends FunctionalTestCase {

  /**
   * {@inheritdoc}
   */
  protected string $sniffSource = 'DrevOps.NamingConventions.LocalVariableSnakeCase';

  #[Group('smoke')]
  public function testSmoke(): void {
    $this->runPhpcs(static::$fixtures . DIRECTORY_SEPARATOR . 'Valid.php');
  }

  public function testSniffDetectsLocalVariableViolations(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'VariableNaming.php',
      [
        [
          'message' => 'Variable "$invalidVariable" is not in snake_case format; try "$invalid_variable"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$anotherInvalid" is not in snake_case format; try "$another_invalid"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$testCamelCase" is not in snake_case format; try "$test_camel_case"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidVar" is not in snake_case format; try "$invalid_var"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snake_case format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$localVar" is not in snake_case format; try "$local_var"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidVar" is not in snake_case format; try "$invalid_var"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
  }

  /**
   * Test that attributed properties are correctly recognized and not flagged.
   */
  public function testAttributedPropertiesAreNotFlagged(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'AttributedProperties.php',
      [
        [
          'message' => 'Variable "$invalidLocalVar" is not in snake_case format; try "$invalid_local_var"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
  }

  /**
   * Test that only local variables (not parameters) are flagged.
   */
  public function testOnlyLocalVariablesAreFlagged(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'InheritedParameters.php',
      [
        [
          'message' => 'Variable "$localInvalidCamelCase" is not in snake_case format; try "$local_invalid_camel_case"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$localInvalidCamelCase" is not in snake_case format; try "$local_invalid_camel_case"',
          'source' => 'DrevOps.NamingConventions.LocalVariableSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
  }

}
