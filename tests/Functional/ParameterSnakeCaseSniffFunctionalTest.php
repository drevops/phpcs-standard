<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional integration test for ParameterSnakeCaseSniff.
 *
 * This tests the sniff by actually running phpcs as an external command,
 * which is the most reliable way to test PHPCS sniffs.
 */
#[CoversNothing]
class ParameterSnakeCaseSniffFunctionalTest extends FunctionalTestCase {

  /**
   * {@inheritdoc}
   */
  protected string $sniffSource = 'DrevOps.NamingConventions.ParameterSnakeCase';

  #[Group('smoke')]
  public function testSmoke(): void {
    $this->runPhpcs(static::$fixtures . DIRECTORY_SEPARATOR . 'Valid.php');
  }

  public function testSniffDetectsParameterViolations(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'VariableNaming.php',
      [
        [
          'message' => 'Variable "$invalidParam" is not in snake_case format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.ParameterSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snake_case format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.ParameterSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snake_case format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.ParameterSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
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
          'message' => 'Variable "$invalidNonInheritedParamOne" is not in snake_case format; try "$invalid_non_inherited_param_one"',
          'source' => 'DrevOps.NamingConventions.ParameterSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidNonInheritedParamTwo" is not in snake_case format; try "$invalid_non_inherited_param_two"',
          'source' => 'DrevOps.NamingConventions.ParameterSnakeCase.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
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

}
