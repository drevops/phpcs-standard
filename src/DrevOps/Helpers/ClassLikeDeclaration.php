<?php

declare(strict_types=1);

namespace DrevOps\Helpers;

/**
 * Inheritance data of a class, interface, trait or enum declaration.
 */
final readonly class ClassLikeDeclaration {

  /**
   * Constructs a ClassLikeDeclaration.
   *
   * @param string|null $name
   *   Fully qualified name without a leading backslash, or NULL for an
   *   anonymous class.
   * @param list<string> $ancestors
   *   Fully qualified names of the extended classes, the implemented or
   *   extended interfaces and the used traits.
   * @param array<string, list<string>> $methods
   *   Parameter names, including the leading '$', of the non-private methods
   *   keyed by lowercase method name.
   */
  public function __construct(public ?string $name, public array $ancestors, public array $methods) {}

}
