<?php

declare(strict_types=1);

namespace DrevOps\Helpers;

/**
 * Maps the namespaces of Drupal extensions to their directories.
 *
 * Drupal registers extension namespaces at runtime rather than through
 * Composer. 'Drupal\<extension>\' maps to the extension's 'src' directory and
 * 'Drupal\Tests\<extension>\' to its 'tests/src' directory, where the
 * extension is a module, profile or theme found by its '*.info.yml' file.
 */
final class DrupalNamespaceMap {

  /**
   * Suffix of the file that declares an extension.
   */
  protected const string INFO_FILE_SUFFIX = '.info.yml';

  /**
   * Directories that Drupal's extension discovery never descends into.
   *
   * 'config' and 'tests' are scanned: core has a module named 'config', and
   * test modules live under 'tests'.
   *
   * @see \Drupal\Core\Extension\Discovery\RecursiveExtensionFilterIterator
   */
  protected const array SKIPPED_DIRECTORIES = [
    'src',
    'lib',
    'vendor',
    'assets',
    'css',
    'files',
    'images',
    'js',
    'misc',
    'templates',
    'includes',
    'fixtures',
    'Drupal',
    'node_modules',
    'bower_components',
  ];

  /**
   * Extension directories keyed by machine name, or NULL before the scan.
   *
   * @var array<string, string>|null
   */
  protected ?array $extensions = NULL;

  /**
   * Constructs a DrupalNamespaceMap.
   *
   * @param string $root
   *   The Drupal root.
   */
  public function __construct(protected string $root) {}

  /**
   * Finds the source file of a class in a Drupal extension namespace.
   *
   * @param string $class_name
   *   The fully qualified class name without a leading backslash.
   *
   * @return string|null
   *   The source file path, or NULL when no extension holds the class.
   */
  public function findFile(string $class_name): ?string {
    $segments = explode('\\', $class_name);

    if (array_shift($segments) !== 'Drupal') {
      return NULL;
    }

    $source_directory = '/src/';

    if (($segments[0] ?? NULL) === 'Tests') {
      array_shift($segments);
      $source_directory = '/tests/src/';
    }

    $machine_name = array_shift($segments);

    // Checked before the scan, so a name that cannot belong to an extension
    // never triggers it.
    if ($machine_name === NULL || $segments === []) {
      return NULL;
    }

    $this->extensions ??= $this->scan();

    if (!isset($this->extensions[$machine_name])) {
      return NULL;
    }

    $path = $this->extensions[$machine_name] . $source_directory . implode('/', $segments) . '.php';

    return is_file($path) ? $path : NULL;
  }

  /**
   * Finds the extensions under the Drupal root.
   *
   * @return array<string, string>
   *   Extension directories keyed by machine name.
   */
  protected function scan(): array {
    $extensions = [];
    $visited = [];

    foreach ($this->getSearchDirectories() as $directory) {
      $this->scanDirectory($directory, $extensions, $visited);
    }

    return $extensions;
  }

  /**
   * Gets the directories to search for extensions.
   *
   * @return list<string>
   *   Directories from the lowest precedence to the highest. An extension
   *   found later replaces an earlier one with the same machine name, so
   *   site-specific extensions win over shared ones, and those over core.
   */
  protected function getSearchDirectories(): array {
    $bases = [$this->root . '/core', $this->root];

    foreach ($this->readDirectory($this->root . '/sites') as $site) {
      $bases[] = $this->root . '/sites/' . $site;
    }

    $directories = [];

    foreach ($bases as $base) {
      $directories[] = $base . '/modules';
      $directories[] = $base . '/profiles';
      $directories[] = $base . '/themes';
    }

    return $directories;
  }

  /**
   * Records the extensions in a directory and its subdirectories.
   *
   * @param string $directory
   *   The directory to scan.
   * @param array<string, string> $extensions
   *   Extension directories keyed by machine name.
   * @param array<string, bool> $visited
   *   Scanned directories keyed by real path.
   */
  protected function scanDirectory(string $directory, array &$extensions, array &$visited): void {
    $real_directory = realpath($directory);

    // A symlink can lead back to a directory that is already scanned.
    if ($real_directory === FALSE || isset($visited[$real_directory])) {
      return;
    }

    $visited[$real_directory] = TRUE;

    foreach ($this->readDirectory($real_directory) as $entry) {
      $path = $real_directory . '/' . $entry;

      if (str_ends_with($entry, self::INFO_FILE_SUFFIX) && is_file($path)) {
        $extensions[substr($entry, 0, -strlen(self::INFO_FILE_SUFFIX))] = $real_directory;
      }
      elseif (is_dir($path) && !in_array($entry, self::SKIPPED_DIRECTORIES, TRUE)) {
        $this->scanDirectory($path, $extensions, $visited);
      }
    }
  }

  /**
   * Reads the entries of a directory.
   *
   * @param string $directory
   *   The directory to read.
   *
   * @return list<string>
   *   Entry names in alphabetical order, without hidden entries. Empty when
   *   the path is not a readable directory.
   */
  protected function readDirectory(string $directory): array {
    // PHP_CodeSniffer turns the warning of a failed scandir() into an
    // exception.
    if (!is_dir($directory) || !is_readable($directory)) {
      return [];
    }

    $entries = scandir($directory) ?: [];

    return array_values(array_filter($entries, static fn(string $entry): bool => !str_starts_with($entry, '.')));
  }

}
