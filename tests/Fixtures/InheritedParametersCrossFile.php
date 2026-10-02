<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

use DrevOps\PhpcsStandard\Tests\Fixtures\Inheritance\UpstreamInterface;
use DrevOps\PhpcsStandard\Tests\Fixtures\Inheritance\UpstreamParentClass as BaseClass;
use DrevOps\PhpcsStandard\Tests\Fixtures\Inheritance\UpstreamTrait;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

// Interface extending an interface declared in another file.
interface CrossFileChildInterface extends UpstreamInterface {

  // Redeclared from the parent interface: exempt.
  public function upstreamMethod(string $upstreamParam): void;

  // Declared here first: checked.
  public function childInterfaceMethod(string $childInterfaceParam): void;

}

// Class extending and implementing ancestors declared in other files.
class CrossFileChildClass extends BaseClass implements UpstreamInterface {

  use UpstreamTrait;

  // Keeps the parent constructor name (exempt) and adds one (checked).
  public function __construct(array $constructorParam, string $extraConstructorParam) {
    parent::__construct($constructorParam);
  }

  public function upstreamMethod(string $upstreamParam): void {
  }

  public function parentInterfaceMethod(int $parentInterfaceParam): void {
  }

  public function parentMethod(string $parentParam): void {
  }

  protected function protectedParentMethod(string $protectedParam): void {
  }

  public function abstractParentMethod(string $abstractParam): void {
  }

  public function grandparentMethod(string $grandparentParam): void {
  }

  public function caseinsensitivemethod(string $caseParam): void {
  }

  public function traitMethod(string $traitParam): void {
  }

  public function nestedTraitMethod(string $nestedTraitParam): void {
  }

  // The parent method is private, so it is not inherited: checked.
  public function privateParentMethod(string $privateParam): void {
  }

  // Not declared by any ancestor: checked.
  public function ownMethod(string $ownParam): void {
  }

  // Private methods never inherit a signature: checked.
  private function privateHelper(string $helperParam): void {
  }

}

// Sniff implementing an interface loaded by PHP_CodeSniffer itself.
class CrossFileSniff implements Sniff {

  public function register(): array {
    return [];
  }

  // Names declared by the PHP_CodeSniffer interface: exempt.
  public function process(File $phpcsFile, $stackPtr) {
  }

  // Not declared by the interface: checked.
  public function helper(File $phpcsFile): void {
  }

}

// Exception extending an internal class.
class CrossFileException extends \RuntimeException {

  // Keeps the internal constructor names (exempt) and adds one (checked).
  public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = NULL, string $resourceName = '') {
    parent::__construct($message, $code, $previous);
  }

  // Not declared by any ancestor: checked.
  public static function fromResource(string $resourceName): self {
    return new self();
  }

}

// Collection implementing internal interfaces.
class CrossFileCollection implements \Countable, \IteratorAggregate {

  public function count(): int {
    return 0;
  }

  public function getIterator(): \Iterator {
    return new \ArrayIterator([]);
  }

  // Not declared by any ancestor: checked.
  public function addItem(string $itemName): void {
  }

}

// Class whose parent cannot be resolved.
class CrossFileUnresolvedChild extends \Vendor\Missing\BaseClass implements UpstreamInterface {

  // Found in a resolved ancestor, so only its names are exempt.
  public function upstreamMethod(string $upstreamParam, ?string $extraParam = NULL): void {
  }

  public function parentInterfaceMethod(int $parentInterfaceParam): void {
  }

  // Not found in a resolved ancestor; the missing parent may declare it: exempt.
  public function unknownMethod(string $unknownParam): void {
  }

  // Private methods never inherit a signature: checked.
  private function privateHelper(string $helperParam): void {
  }

}

// Enum implementing an interface declared in another file.
enum CrossFileEnum: string implements UpstreamInterface {

  case Active = 'active';

  public function upstreamMethod(string $upstreamParam): void {
  }

  public function parentInterfaceMethod(int $parentInterfaceParam): void {
  }

  // Not declared by any ancestor: checked.
  public function enumMethod(string $enumParam): void {
  }

}

// Anonymous class extending a class declared in another file.
function cross_file_factory(): BaseClass {
  return new class([]) extends BaseClass {

    public function abstractParentMethod(string $abstractParam): void {
    }

    // Not declared by any ancestor: checked.
    public function anonymousMethod(string $anonymousParam): void {
    }

  };
}
