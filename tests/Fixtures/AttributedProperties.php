<?php

declare(strict_types=1);

/**
 * Test fixture for attributed properties.
 *
 * This tests that properties with PHP 8 attributes are correctly identified
 * as properties and not misclassified as local variables.
 */
class AttributedPropertiesTest {

  /**
   * Regular property without attributes (should be ignored).
   */
  public string $regularProperty;

  /**
   * Property with single attribute (should be ignored).
   */
  #[\SomeAttribute]
  public string $attributedProperty;

  /**
   * Property with multiple attributes (should be ignored).
   */
  #[\First]
  #[\Second]
  public string $multiAttributedProperty;

  /**
   * Property with attribute that has parameters (should be ignored).
   */
  #[\Route('/api/endpoint', name: 'api_endpoint')]
  private string $complexAttributedProperty;

  /**
   * Static property with attribute (should be ignored).
   */
  #[\Deprecated]
  public static string $staticAttributedProperty;

  /**
   * Readonly property with attribute (should be ignored).
   */
  #[\Serializable]
  public readonly string $readonlyAttributedProperty;

  /**
   * Test method to verify local variables are still checked.
   */
  public function testMethod(): void {
    // This local variable should be flagged as invalid.
    $invalidLocalVar = 'test';

    // This should be valid.
    $valid_local_var = 'test';
  }

}
