<?php

declare(strict_types=1);

namespace DrevOps\Helpers;

use Composer\Autoload\ClassLoader;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Files\File;

/**
 * Finds the parameter names that ancestors declare for a method.
 *
 * Ancestors are looked up in the file being checked, then among the classes
 * already loaded in the process, then in the source files located by the
 * Composer class loaders. Located files are tokenized and never included, so
 * no project code is executed.
 */
final class InheritanceResolver {

  /**
   * The parser for class-like declarations.
   */
  protected ClassLikeParser $parser;

  /**
   * Locates the source file of a class.
   *
   * @var \Closure(string): (string|null)
   */
  protected \Closure $sourceLocator;

  /**
   * Looked-up ancestor declarations keyed by lowercase class name.
   *
   * NULL marks a class that could not be resolved.
   *
   * @var array<string, \DrevOps\Helpers\ClassLikeDeclaration|null>
   */
  protected array $declarations = [];

  /**
   * Declarations of parsed source files keyed by path.
   *
   * @var array<string, array<string, \DrevOps\Helpers\ClassLikeDeclaration>>
   */
  protected array $sourceFiles = [];

  /**
   * Identifies the token stream the local declarations were parsed from.
   */
  protected string $localKey = '';

  /**
   * Declarations of the file being checked keyed by token position.
   *
   * @var array<int, \DrevOps\Helpers\ClassLikeDeclaration>
   */
  protected array $localDeclarations = [];

  /**
   * Declarations of the file being checked keyed by lowercase class name.
   *
   * @var array<string, \DrevOps\Helpers\ClassLikeDeclaration>
   */
  protected array $localDeclarationsByName = [];

  /**
   * Constructs an InheritanceResolver.
   *
   * @param (\Closure(string): (string|null))|null $source_locator
   *   Returns the source file path of a class, or NULL when it is unknown.
   *   Defaults to the Composer class loaders.
   */
  public function __construct(?\Closure $source_locator = NULL) {
    $this->parser = new ClassLikeParser();
    $this->sourceLocator = $source_locator ?? self::locateWithComposer(...);
  }

  /**
   * Gets the parameter names that ancestors declare for a method.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being checked.
   * @param int $function_ptr
   *   The position of the function token.
   *
   * @return list<string>|null
   *   Parameter names, including the leading '$', from every ancestor that
   *   declares the method. An empty array when no ancestor declares it. NULL
   *   when no resolved ancestor declares it and an ancestor is unresolved.
   */
  public function getInheritedParameterNames(File $phpcs_file, int $function_ptr): ?array {
    $tokens = $phpcs_file->getTokens();

    if ($tokens[$function_ptr]['code'] !== T_FUNCTION) {
      return [];
    }

    $conditions = $tokens[$function_ptr]['conditions'];
    $class_ptr = array_key_last($conditions);

    if ($class_ptr === NULL || !in_array($conditions[$class_ptr], ClassLikeParser::CLASS_LIKE_TOKENS, TRUE)) {
      return [];
    }

    // Private methods are not inherited, so they never implement or override
    // an ancestor signature.
    if ($phpcs_file->getMethodProperties($function_ptr)['scope'] === 'private') {
      return [];
    }

    $this->loadLocalDeclarations($phpcs_file);

    // @codeCoverageIgnoreStart
    // Conditions only reference class-likes that have a scope, and the parser
    // records all of them. This guards malformed token streams.
    if (!isset($this->localDeclarations[$class_ptr])) {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    $declaration = $this->localDeclarations[$class_ptr];
    $method_name = strtolower((string) $phpcs_file->getDeclarationName($function_ptr));

    $parameter_names = [];
    $is_declared = FALSE;
    $is_resolved = TRUE;
    $visited = $declaration->name === NULL ? [] : [strtolower($declaration->name) => TRUE];
    $queue = $declaration->ancestors;

    while ($queue !== []) {
      $ancestor_name = array_shift($queue);
      $key = strtolower($ancestor_name);

      if (isset($visited[$key])) {
        continue;
      }

      $visited[$key] = TRUE;
      $ancestor = $this->findDeclaration($ancestor_name, $phpcs_file);

      if (!$ancestor instanceof ClassLikeDeclaration) {
        $is_resolved = FALSE;
        continue;
      }

      if (isset($ancestor->methods[$method_name])) {
        $is_declared = TRUE;
        $parameter_names = array_merge($parameter_names, $ancestor->methods[$method_name]);
      }

      $queue = array_merge($queue, $ancestor->ancestors);
    }

    if ($is_declared) {
      return array_values(array_unique($parameter_names));
    }

    return $is_resolved ? [] : NULL;
  }

  /**
   * Parses the declarations of the file being checked.
   *
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being checked.
   */
  protected function loadLocalDeclarations(File $phpcs_file): void {
    // The fixer re-tokenizes the same File object on every pass.
    $key = sprintf('%s:%d:%d:%d', $phpcs_file->path, spl_object_id($phpcs_file), $phpcs_file->fixer->loops, $phpcs_file->numTokens);

    if ($key === $this->localKey) {
      return;
    }

    $this->localKey = $key;
    $this->localDeclarations = $this->parser->parse($phpcs_file);
    $this->localDeclarationsByName = $this->indexByName($this->localDeclarations);
  }

  /**
   * Finds the declaration of an ancestor.
   *
   * @param string $class_name
   *   The fully qualified class name.
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being checked.
   *
   * @return \DrevOps\Helpers\ClassLikeDeclaration|null
   *   The declaration, or NULL when it cannot be resolved.
   */
  protected function findDeclaration(string $class_name, File $phpcs_file): ?ClassLikeDeclaration {
    $key = strtolower($class_name);

    if (isset($this->localDeclarationsByName[$key])) {
      return $this->localDeclarationsByName[$key];
    }

    if (!array_key_exists($key, $this->declarations)) {
      $this->declarations[$key] = $this->reflectDeclaration($class_name) ?? $this->parseDeclaration($class_name, $phpcs_file);
    }

    return $this->declarations[$key];
  }

  /**
   * Reads the declaration of a class already loaded in the process.
   *
   * Autoloading stays disabled, so no class is loaded by the lookup.
   *
   * @param string $class_name
   *   The fully qualified class name.
   *
   * @return \DrevOps\Helpers\ClassLikeDeclaration|null
   *   The declaration, or NULL when the class is not loaded.
   */
  protected function reflectDeclaration(string $class_name): ?ClassLikeDeclaration {
    if (!class_exists($class_name, FALSE) && !interface_exists($class_name, FALSE) && !trait_exists($class_name, FALSE)) {
      return NULL;
    }

    $reflection = new \ReflectionClass($class_name);
    $parent = $reflection->getParentClass();
    $ancestors = array_merge($parent === FALSE ? [] : [$parent->getName()], $reflection->getInterfaceNames(), $reflection->getTraitNames());
    $methods = [];

    foreach ($reflection->getMethods() as $method) {
      if ($method->isPrivate() || $method->getDeclaringClass()->getName() !== $reflection->getName()) {
        continue;
      }

      $parameter_names = array_map(static fn(\ReflectionParameter $parameter): string => '$' . $parameter->getName(), $method->getParameters());
      $methods[strtolower($method->getName())] = $parameter_names;
    }

    return new ClassLikeDeclaration($reflection->getName(), array_values($ancestors), $methods);
  }

  /**
   * Reads the declaration of a class from its located source file.
   *
   * @param string $class_name
   *   The fully qualified class name.
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being checked, which provides the ruleset and config.
   *
   * @return \DrevOps\Helpers\ClassLikeDeclaration|null
   *   The declaration, or NULL when no located source file declares it.
   */
  protected function parseDeclaration(string $class_name, File $phpcs_file): ?ClassLikeDeclaration {
    try {
      $path = ($this->sourceLocator)($class_name);

      if (!is_string($path)) {
        return NULL;
      }

      $this->sourceFiles[$path] ??= $this->parseSourceFile($path, $phpcs_file);

      return $this->sourceFiles[$path][strtolower($class_name)] ?? NULL;
    }
    catch (\Exception) {
      // Reading an ancestor must not abort the check of the current file, so
      // a failure marks the ancestor unresolved.
      return NULL;
    }
  }

  /**
   * Parses the declarations of a source file.
   *
   * @param string $path
   *   The source file path.
   * @param \PHP_CodeSniffer\Files\File $phpcs_file
   *   The file being checked, which provides the ruleset and config.
   *
   * @return array<string, \DrevOps\Helpers\ClassLikeDeclaration>
   *   Declarations keyed by lowercase class name.
   */
  protected function parseSourceFile(string $path, File $phpcs_file): array {
    // PHP_CodeSniffer turns PHP warnings into exceptions, and a stale
    // classmap can point at a file that no longer exists.
    if (!is_file($path) || !is_readable($path)) {
      return [];
    }

    $source_file = new DummyFile((string) file_get_contents($path), $phpcs_file->ruleset, $phpcs_file->config);
    $source_file->parse();

    return $this->indexByName($this->parser->parse($source_file));
  }

  /**
   * Indexes named declarations by lowercase class name.
   *
   * @param array<int, \DrevOps\Helpers\ClassLikeDeclaration> $declarations
   *   The declarations.
   *
   * @return array<string, \DrevOps\Helpers\ClassLikeDeclaration>
   *   Declarations keyed by lowercase class name. The first declaration of a
   *   name wins.
   */
  protected function indexByName(array $declarations): array {
    $index = [];

    foreach ($declarations as $declaration) {
      if ($declaration->name !== NULL) {
        $index[strtolower($declaration->name)] ??= $declaration;
      }
    }

    return $index;
  }

  /**
   * Locates the source file of a class through the Composer class loaders.
   *
   * @param string $class_name
   *   The fully qualified class name.
   *
   * @return string|null
   *   The source file path, or NULL when no class loader maps the class.
   */
  protected static function locateWithComposer(string $class_name): ?string {
    // @codeCoverageIgnoreStart
    if (!class_exists(ClassLoader::class, FALSE)) {
      return NULL;
    }
    // @codeCoverageIgnoreEnd
    foreach (ClassLoader::getRegisteredLoaders() as $loader) {
      $path = $loader->findFile($class_name);

      if (is_string($path)) {
        return $path;
      }
    }

    return NULL;
  }

}
