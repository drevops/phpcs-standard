<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Fixtures;

class ClassWithNestedFunctions {

  // Closure parameter used in the closure body.
  public function methodWithClosure(): \Closure {
    return function ($closureParam) {
      return $closureParam;
    };
  }

  // Arrow function parameter used in the arrow function body.
  public function methodWithArrowFunction(): \Closure {
    return fn($arrowParam) => $arrowParam * 2;
  }

  // Static closure and static arrow function.
  public function methodWithStaticFunctions(): array {
    return [
      static function ($staticClosureParam) {
        return $staticClosureParam;
      },
      static fn($staticArrowParam) => $staticArrowParam,
    ];
  }

  // Arrow function returning by reference, with a by-reference parameter.
  public function methodWithReferenceArrowFunction(): \Closure {
    return fn&(array &$referenceParam) => $referenceParam;
  }

  // Closure and arrow function passed as call arguments.
  public function methodWithCallbacks(array $items): array {
    $mapped = array_map(function ($mapItem) {
      return $mapItem;
    }, $items);

    return array_filter($mapped, fn($filterItem) => $filterItem !== NULL);
  }

  // Method parameter imported into a closure.
  public function methodWithUse($importedParam): \Closure {
    return function () use ($importedParam) {
      return $importedParam;
    };
  }

  // Method parameter imported into a closure by reference.
  public function methodWithReferenceUse(array $referenceImportedParam): \Closure {
    return function () use (&$referenceImportedParam) {
      $referenceImportedParam[] = 1;
    };
  }

  // Method parameter captured by an arrow function.
  public function methodWithCapture($capturedParam): \Closure {
    return fn($item_value) => $item_value . $capturedParam;
  }

  // Parameters captured across nested arrow functions.
  public function methodWithNestedArrowFunctions($outerArrowParam): \Closure {
    return fn($middleArrowParam) => fn($innerArrowParam) => $outerArrowParam + $middleArrowParam + $innerArrowParam;
  }

  // Closure inside an arrow function, and an arrow function inside that
  // closure.
  public function methodWithMixedNesting($mixedMethodParam): \Closure {
    return fn($mixedArrowParam) => function ($mixedClosureParam) use ($mixedArrowParam, $mixedMethodParam) {
      return fn($mixedInnerParam) => $mixedInnerParam . $mixedClosureParam . $mixedArrowParam . $mixedMethodParam;
    };
  }

  // Closure parameter shadowing the method parameter.
  public function methodWithShadowedParam($shadowedParam): \Closure {
    return function ($shadowedParam) {
      return $shadowedParam;
    };
  }

  // Match expression in an arrow function body.
  public function methodWithMatch($matchSubject): \Closure {
    return fn($matchDefault) => match ($matchSubject) {
      1 => $matchSubject,
      default => $matchDefault,
    };
  }

  // Locals of closures and arrow functions are checked.
  public function methodWithNestedLocals($notImportedParam): \Closure {
    $outerLocal = 1;

    $closure = function () use ($outerLocal) {
      $closureLocal = $outerLocal;

      // Not imported, so this is a local of the closure.
      return $closureLocal . $notImportedParam;
    };

    return fn() => $arrowLocal = $outerLocal;
  }

}

function nested_functions_factory($factoryParam): object {
  return new class {

    // Anonymous class method parameter used in the method body.
    public function anonymousMethod($anonymousParam) {
      return $anonymousParam;
    }

    // Anonymous class methods do not see the enclosing function's variables.
    public function anonymousMethodWithOuterName() {
      return $factoryParam;
    }

  };
}

// Plain function parameter used in the function body.
function nested_functions_plain($plainParam) {
  return $plainParam;
}

// Global variable named like a parameter of a function declared above.
$plainParam = 1;
