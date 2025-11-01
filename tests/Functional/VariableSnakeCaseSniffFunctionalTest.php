<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional integration test for the DrevOps PHPCS standard.
 *
 * This tests the sniff by actually running phpcs as an external command,
 * which is the most reliable way to test PHPCS sniffs.
 */
#[CoversNothing]
class VariableSnakeCaseSniffFunctionalTest extends FunctionalTestCase {

  #[Group('smoke')]
  public function testSmoke(): void {
    $this->runPhpcs(static::$fixtures . DIRECTORY_SEPARATOR . 'Valid.php');
  }

  public function testSniffDetectsViolations(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'VariableNaming.php',
      [
        [
          'message' => 'Variable "$invalidVariable" is not in snake_case format; try "$invalid_variable"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 20,
          'column' => 5,
        ],
        [
          'message' => 'Variable "$anotherInvalid" is not in snake_case format; try "$another_invalid"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 21,
          'column' => 5,
        ],
        [
          'message' => 'Variable "$testCamelCase" is not in snake_case format; try "$test_camel_case"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 22,
          'column' => 5,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snake_case format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 33,
          'column' => 66,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snake_case format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 34,
          'column' => 30,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snake_case format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 40,
          'column' => 40,
        ],
        [
          'message' => 'Variable "$invalidVar" is not in snake_case format; try "$invalid_var"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 43,
          'column' => 7,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snake_case format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 45,
          'column' => 29,
        ],
        [
          'message' => 'Variable "$localVar" is not in snake_case format; try "$local_var"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 57,
          'column' => 5,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snake_case format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 63,
          'column' => 59,
        ],
        [
          'message' => 'Variable "$invalidVar" is not in snake_case format; try "$invalid_var"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 66,
          'column' => 3,
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
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 54,
          'column' => 5,
        ],
      ]
    );
  }

  /**
   * Test that inherited parameters are NOT flagged.
   */
  public function testInheritedParametersAreNotFlagged(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'InheritedParameters.php',
      [
        [
          'message' => 'Variable "$localInvalidCamelCase" is not in snake_case format; try "$local_invalid_camel_case"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 24,
          'column' => 5,
        ],
        [
          'message' => 'Variable "$localInvalidCamelCase" is not in snake_case format; try "$local_invalid_camel_case"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 36,
          'column' => 5,
        ],
        [
          'message' => 'Variable "$invalidNonInheritedParamOne" is not in snake_case format; try "$invalid_non_inherited_param_one"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 43,
          'column' => 48,
        ],
        [
          'message' => 'Variable "$invalidNonInheritedParamTwo" is not in snake_case format; try "$invalid_non_inherited_param_two"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 43,
          'column' => 78,
        ],
        [
          'message' => 'Variable "$invalidNonInheritedParamOne" is not in snake_case format; try "$invalid_non_inherited_param_one"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 45,
          'column' => 20,
        ],
        [
          'message' => 'Variable "$invalidNonInheritedParamTwo" is not in snake_case format; try "$invalid_non_inherited_param_two"',
          'source' => 'DrevOps.NamingConventions.VariableSnakeCase.VariableNotSnakeCase',
          'severity' => 5,
          'fixable' => TRUE,
          'type' => 'ERROR',
          'line' => 45,
          'column' => 51,
        ],
      ]
    );
  }

}
