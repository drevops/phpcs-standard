<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
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
   * Test that inherited parameters are NOT flagged.
   */
  public function testInheritedParametersAreNotFlagged(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'InheritedParameters.php',
      [
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

}
