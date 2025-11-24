<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

class ParameterDocblockMismatch {

  /**
   * Method with invalid parameter name and matching docblock.
   *
   * @param string $invalidParam
   *   The invalid parameter.
   * @param int $anotherInvalid
   *   Another invalid parameter.
   *
   * @return void
   */
  public function methodWithDocblock(string $invalidParam, int $anotherInvalid): void {
    echo $invalidParam . $anotherInvalid;
  }

  /**
   * Method with mixed valid/invalid parameters.
   *
   * @param string $valid_param
   *   A valid parameter.
   * @param int $invalidParam
   *   An invalid parameter.
   *
   * @return string
   */
  public function mixedParameters(string $valid_param, int $invalidParam): string {
    return $valid_param . $invalidParam;
  }

  /**
   * Method with multiline param descriptions.
   *
   * @param string $invalidParam
   *   This is a parameter with a very long description
   *   that spans multiple lines in the docblock.
   * @param array $anotherInvalid
   *   Another parameter with description.
   *
   * @return void
   */
  public function multilineDocblock(string $invalidParam, array $anotherInvalid): void {
    print_r($invalidParam);
    print_r($anotherInvalid);
  }

  /**
   * Method with complex types.
   *
   * @param array<string, mixed> $invalidParam
   *   Complex array type.
   * @param \DateTime|null $optionalInvalid
   *   Optional parameter with union type.
   *
   * @return void
   */
  public function complexTypes(array $invalidParam, ?\DateTime $optionalInvalid = NULL): void {
    // Implementation.
  }

  /**
   * Method with no docblock tags.
   *
   * This method has a docblock but no @param tags.
   */
  public function noParamTags(string $invalidParam): void {
    echo $invalidParam;
  }

  /**
   * Method with extra docblock content.
   *
   * @see SomeClass
   * @param string $invalidParam
   *   The parameter.
   * @throws \Exception
   *   When something goes wrong.
   *
   * @return void
   */
  public function extraTags(string $invalidParam): void {
    echo $invalidParam;
  }

}

/**
 * Standalone function with docblock.
 *
 * @param string $invalidParam
 *   The invalid parameter.
 * @param int $anotherInvalid
 *   Another invalid parameter.
 *
 * @return string
 */
function standaloneFunctionWithDocblock(string $invalidParam, int $anotherInvalid): string {
  return $invalidParam . $anotherInvalid;
}
