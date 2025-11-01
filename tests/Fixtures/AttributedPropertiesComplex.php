<?php

declare(strict_types=1);

/**
 * Test fixture for complex attributed properties scenarios.
 */
class AttributedPropertiesComplex {

  // Attribute on same line as property.
  #[\SomeAttribute] public string $sameLineProperty;

  // Attribute with complex parameters.
  #[\ORM\Column(type: 'string', length: 255, nullable: true)]
  protected string $ormProperty;

  // Multiple attributes with no line break.
  #[\First] #[\Second] private string $multiSameLineProperty;

  // Promoted constructor property with attribute.
  public function __construct(
    #[\Inject]
    public string $promotedProperty,
    #[\Inject]
    private string $anotherPromotedProperty,
  ) {
  }

  // Property with attribute and no type hint (using mixed implicitly).
  #[\Legacy]
  public $untypedProperty;

}
