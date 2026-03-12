<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

/**
 * Fixture for testing variable references in comments during auto-fix.
 *
 * Tests various comment styles, positions, and edge cases to verify that
 * phpcbf correctly updates (or skips) variable references in comments.
 *
 * @phpcs:disable Drupal.Commenting.FunctionComment
 * @phpcs:disable Drupal.Commenting.DocComment
 */
class CommentVariableNaming {

  // =========================================================================
  // Variables with doc comments — violations expected.
  // =========================================================================

  /**
   * Single-line doc comment with simple type.
   */
  public function singleLineDocComment(): void {
    /** @var \SomeClass $moduleHandler */
    $moduleHandler = get_service('module_handler');
  }

  /**
   * Single-line doc comment with FQN type (leading backslash).
   */
  public function fqnTypeLeadingBackslash(): void {
    /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
    $moduleHandler = get_service('module_handler');
  }

  /**
   * Single-line doc comment with FQN type (no leading backslash).
   */
  public function fqnTypeNoLeadingBackslash(): void {
    /** @var Drupal\Core\Extension\ModuleHandler $moduleHandler */
    $moduleHandler = get_service('module_handler');
  }

  /**
   * Single-line doc comment with very long FQN type.
   */
  public function longFqnType(): void {
    /** @var \Very\Long\Namespace\Path\To\SomeClass $moduleHandler */
    $moduleHandler = get_service('module_handler');
  }

  /**
   * Multi-line doc comment above variable.
   */
  public function multiLineDocComment(): void {
    /**
     * @var \Drupal\Core\Extension\ModuleHandler $moduleHandler
     *   The module handler service.
     */
    $moduleHandler = get_service('module_handler');
  }

  /**
   * Doc comment with a blank line between comment and variable.
   */
  public function docCommentWithBlankLine(): void {
    /** @var \SomeClass $moduleHandler */

    $moduleHandler = get_service('module_handler');
  }

  // =========================================================================
  // Variables with inline comments — violations expected.
  // =========================================================================

  /**
   * Inline comment with variable reference.
   */
  public function inlineComment(): void {
    // $moduleHandler holds the module handler.
    $moduleHandler = get_service('module_handler');
  }

  /**
   * C-style inline comment with variable reference.
   */
  public function cStyleInlineComment(): void {
    /* $moduleHandler holds the module handler. */
    $moduleHandler = get_service('module_handler');
  }

  // =========================================================================
  // Mix of comment types — violations expected.
  // =========================================================================

  /**
   * Both doc and inline comments in the same method.
   */
  public function mixedCommentTypes(): void {
    /** @var \SomeClass $firstHandler */
    $firstHandler = get_service('first');

    // $secondHandler holds the second handler.
    $secondHandler = get_service('second');
  }

  // =========================================================================
  // Comments with multiple variables — violation on variable, but comment
  // fix should be skipped (requires manual fix).
  // =========================================================================

  /**
   * Doc comment referencing multiple variables.
   */
  public function multipleVarsInComment(): void {
    /** @var \SomeClass $moduleHandler See also $otherService */
    $moduleHandler = get_service('module_handler');
  }

  // =========================================================================
  // Code between comment and variable — violation on variable, but comment
  // fix should not apply (search stops at code).
  // =========================================================================

  /**
   * Code statement between comment and variable.
   */
  public function codeBetweenCommentAndVariable(): void {
    /** @var \SomeClass $moduleHandler */
    $valid_other = 1;
    $moduleHandler = get_service('module_handler');
  }

  // =========================================================================
  // Trailing comments on previous statements — violation on variable, but
  // comment fix should not apply (trailing comment is not a preceding comment).
  // =========================================================================

  /**
   * Trailing inline comment on previous statement.
   */
  public function trailingCommentOnPreviousStatement(): void {
    $valid_other = get_service('other'); // $moduleHandler trailing.
    $moduleHandler = get_service('module_handler');
  }

  // =========================================================================
  // Valid variables — no violations expected.
  // =========================================================================

  /**
   * Valid snake_case variable with doc comment.
   */
  public function validDocComment(): void {
    /** @var \SomeClass $module_handler */
    $module_handler = get_service('module_handler');
  }

  /**
   * Valid snake_case variable with inline comment.
   */
  public function validInlineComment(): void {
    // $module_handler holds the module handler.
    $module_handler = get_service('module_handler');
  }

  /**
   * Valid snake_case variable with no comment.
   */
  public function validNoComment(): void {
    $module_handler = get_service('module_handler');
  }

  /**
   * Doc comment with variable but no violation on the actual variable.
   */
  public function docCommentOnValidVariable(): void {
    /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
    $module_handler = get_service('module_handler');
  }

}
