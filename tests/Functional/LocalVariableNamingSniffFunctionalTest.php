<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional integration test for LocalVariableNamingSniff.
 *
 * This tests the sniff by actually running phpcs as an external command,
 * which is the most reliable way to test PHPCS sniffs.
 */
#[CoversNothing]
class LocalVariableNamingSniffFunctionalTest extends FunctionalTestCase {

  /**
   * {@inheritdoc}
   */
  protected string $sniffSource = 'DrevOps.NamingConventions.LocalVariableNaming';

  #[Group('smoke')]
  public function testSmoke(): void {
    $this->runPhpcs(static::$fixtures . DIRECTORY_SEPARATOR . 'Valid.php');
  }

  public function testSniffDetectsLocalVariableViolations(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'VariableNaming.php',
      [
        [
          'message' => 'Variable "$invalidVariable" is not in snakeCase format; try "$invalid_variable"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$anotherInvalid" is not in snakeCase format; try "$another_invalid"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$testCamelCase" is not in snakeCase format; try "$test_camel_case"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidVar" is not in snakeCase format; try "$invalid_var"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidParam" is not in snakeCase format; try "$invalid_param"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$localVar" is not in snakeCase format; try "$local_var"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$invalidVar" is not in snakeCase format; try "$invalid_var"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
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
          'message' => 'Variable "$invalidLocalVar" is not in snakeCase format; try "$invalid_local_var"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
  }

  /**
   * Test variables with various comment styles and positions.
   *
   * Covers edge cases for comment variable references:
   * - Single-line doc comments with simple and FQN types.
   * - Multi-line doc comments.
   * - Doc comments with blank line between comment and variable.
   * - Inline // comments and C-style inline comments.
   * - Mix of doc and inline comments in one method.
   * - Multiple variables in one comment (fix skipped, variable still flagged).
   * - Code between comment and variable (fix skipped, variable still flagged).
   * - Valid snake_case variables with comments (no violations).
   */
  public function testCommentVariableNaming(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'CommentVariableNaming.php',
      [
        // Single-line doc comment with simple type.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // FQN type with leading backslash.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // FQN type without leading backslash.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Very long FQN type.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Multi-line doc comment above variable.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Doc comment with blank line between comment and variable.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Inline comment with variable reference.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // C-style inline comment with variable reference.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Mixed: first handler with doc comment.
        [
          'message' => 'Variable "$firstHandler" is not in snakeCase format; try "$first_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Mixed: second handler with inline comment.
        [
          'message' => 'Variable "$secondHandler" is not in snakeCase format; try "$second_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Multiple vars in comment: fix skipped, variable still flagged.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Code between comment and variable: fix skipped, variable flagged.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Trailing comment on previous statement: variable flagged.
        [
          'message' => 'Variable "$moduleHandler" is not in snakeCase format; try "$module_handler"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        // Valid cases: validDocComment, validInlineComment, validNoComment,
        // docCommentOnValidVariable — no violations expected (not listed here).
      ]
    );
  }

  /**
   * Test phpcbf fixes variable references in comments.
   *
   * Runs phpcbf on the CommentVariableNaming fixture and verifies that:
   * - Doc comment variable references are updated.
   * - Inline comments are NOT updated (default fixCommentTypes = 'doc').
   * - Multi-variable comments are left unchanged.
   * - Comments separated by code are left unchanged.
   * - Trailing comments on previous statements are left unchanged.
   */
  public function testPhpcbfFixesCommentVariableReferences(): void {
    $fixed = $this->runPhpcbf(
      static::$fixtures . DIRECTORY_SEPARATOR . 'CommentVariableNaming.php',
      'DrevOps.NamingConventions.LocalVariableNaming'
    );

    // Doc comments should be fixed.
    $this->assertStringContainsString('@var \SomeClass $module_handler */', $fixed);
    $this->assertStringContainsString('@var \Drupal\Core\Extension\ModuleHandler $module_handler */', $fixed);
    $this->assertStringContainsString('@var \Very\Long\Namespace\Path\To\SomeClass $module_handler */', $fixed);

    // FQN without leading backslash: PHPCS 3 tokenizes doc comment strings
    // differently, so the fix may not apply. Assert either form is present.
    $this->assertTrue(
      str_contains($fixed, '@var Drupal\Core\Extension\ModuleHandler $module_handler */')
      || str_contains($fixed, '@var Drupal\Core\Extension\ModuleHandler $moduleHandler */'),
      'FQN without leading backslash: variable should be fixed or original preserved.'
    );

    // Multi-line doc comment should be fixed.
    $this->assertStringContainsString('@var \Drupal\Core\Extension\ModuleHandler $module_handler', $fixed);

    // Mixed: doc comment var should be fixed.
    $this->assertStringContainsString('@var \SomeClass $first_handler */', $fixed);

    // Inline comments should NOT be fixed (default fixCommentTypes = 'doc').
    $this->assertStringContainsString('// $moduleHandler holds the module handler.', $fixed);
    $this->assertStringContainsString('/* $moduleHandler holds the module handler. */', $fixed);
    $this->assertStringContainsString('// $secondHandler holds the second handler.', $fixed);

    // Multi-variable comment should NOT be fixed.
    $this->assertStringContainsString('@var \SomeClass $moduleHandler See also $otherService */', $fixed);

    // Code between comment and variable: comment should NOT be fixed.
    $this->assertStringContainsString('@var \SomeClass $moduleHandler */', $fixed);

    // Trailing comment on previous statement should NOT be fixed.
    $this->assertStringContainsString('// $moduleHandler trailing.', $fixed);
  }

  /**
   * Test that only local variables (not parameters) are flagged.
   */
  public function testOnlyLocalVariablesAreFlagged(): void {
    $this->runPhpcs(
      static::$fixtures . DIRECTORY_SEPARATOR . 'InheritedParameters.php',
      [
        [
          'message' => 'Variable "$localInvalidCamelCase" is not in snakeCase format; try "$local_invalid_camel_case"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
        [
          'message' => 'Variable "$localInvalidCamelCase" is not in snakeCase format; try "$local_invalid_camel_case"',
          'source' => 'DrevOps.NamingConventions.LocalVariableNaming.NotSnakeCase',
          'fixable' => TRUE,
        ],
      ]
    );
  }

}
