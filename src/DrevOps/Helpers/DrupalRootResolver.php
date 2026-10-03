<?php

declare(strict_types=1);

namespace DrevOps\Helpers;

use Composer\InstalledVersions;

/**
 * Resolves the Drupal root from the 'drupalRoot' sniff property.
 *
 * Without a configured path, the root is the parent of the directory that the
 * 'drupal/core' Composer package is installed into.
 */
final class DrupalRootResolver {

  /**
   * The Composer package that holds Drupal core.
   */
  protected const string CORE_PACKAGE = 'drupal/core';

  /**
   * Finds the install path of the Drupal core package.
   *
   * @var \Closure(): (string|null)
   */
  protected \Closure $corePathFinder;

  /**
   * Constructs a DrupalRootResolver.
   *
   * @param (\Closure(): (string|null))|null $core_path_finder
   *   Returns the install path of the Drupal core package, or NULL when it is
   *   not installed. Defaults to the packages Composer has installed.
   */
  public function __construct(?\Closure $core_path_finder = NULL) {
    $this->corePathFinder = $core_path_finder ?? static fn(): ?string => self::findInstallPath(self::CORE_PACKAGE);
  }

  /**
   * Resolves the Drupal root.
   *
   * @param mixed $setting
   *   The property value. FALSE turns discovery off. A non-empty string is the
   *   path to the Drupal root, and a relative path resolves from the working
   *   directory. Any other value detects the root from Drupal core's install
   *   path.
   *
   * @return string|null
   *   The absolute path of the Drupal root, or NULL when there is none.
   *
   * @throws \RuntimeException
   *   When the configured path does not contain core/lib/Drupal.php.
   */
  public function resolve(mixed $setting): ?string {
    if ($setting === FALSE) {
      return NULL;
    }

    if (!is_string($setting) || trim($setting) === '') {
      return $this->detect();
    }

    $root = realpath($setting);

    if ($root === FALSE || !self::isDrupalRoot($root)) {
      throw new \RuntimeException(sprintf('Invalid drupalRoot "%s": core/lib/Drupal.php not found. Relative paths resolve from %s.', $setting, (string) getcwd()));
    }

    return $root;
  }

  /**
   * Detects the Drupal root from Drupal core's install path.
   *
   * @return string|null
   *   The absolute path of the Drupal root, or NULL when Drupal core is not
   *   installed in a Drupal root.
   */
  protected function detect(): ?string {
    $core_path = ($this->corePathFinder)();

    if ($core_path === NULL) {
      return NULL;
    }

    // The parent is taken before symlinks resolve, so a symlinked core
    // directory still gives the directory that holds it.
    $root = realpath(dirname($core_path));

    return $root !== FALSE && self::isDrupalRoot($root) ? $root : NULL;
  }

  /**
   * Checks if a directory is a Drupal root.
   *
   * @param string $path
   *   The directory path.
   *
   * @return bool
   *   TRUE if the directory contains core/lib/Drupal.php.
   */
  protected static function isDrupalRoot(string $path): bool {
    return is_file($path . '/core/lib/Drupal.php');
  }

  /**
   * Finds the install path of a Composer package.
   *
   * @param string $package_name
   *   The package name.
   *
   * @return string|null
   *   The install path, or NULL when the package is not installed.
   */
  protected static function findInstallPath(string $package_name): ?string {
    if (!class_exists(InstalledVersions::class) || !InstalledVersions::isInstalled($package_name)) {
      return NULL;
    }

    return InstalledVersions::getInstallPath($package_name);
  }

}
