<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use DrevOps\Helpers\DrupalRootResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DrupalRootResolver.
 *
 * Relative paths in data sets resolve from the project root, which every test
 * uses as the working directory.
 */
#[CoversClass(DrupalRootResolver::class)]
class DrupalRootResolverTest extends TestCase {

  /**
   * The working directory before the test.
   */
  protected string $originalCwd;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->originalCwd = (string) getcwd();
    chdir(dirname(__DIR__, 2));
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    chdir($this->originalCwd);

    parent::tearDown();
  }

  /**
   * Test resolving the Drupal root.
   *
   * @param mixed $setting
   *   The 'drupalRoot' property value.
   * @param string|null $core_path
   *   The install path of Drupal core relative to the project root, or NULL
   *   when Drupal core is not installed.
   * @param string|null $expected
   *   The expected root relative to the project root, or NULL.
   */
  #[DataProvider('dataProviderResolve')]
  public function testResolve(mixed $setting, ?string $core_path, ?string $expected): void {
    $project_root = (string) getcwd();
    $resolver = new DrupalRootResolver(static fn(): ?string => $core_path === NULL ? NULL : $project_root . '/' . $core_path);

    $this->assertSame($expected === NULL ? NULL : realpath($project_root . '/' . $expected), $resolver->resolve($setting));
  }

  /**
   * Data provider for testResolve.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderResolve(): array {
    $core_path = 'tests/Fixtures/Drupal/core';
    $drupal_root = 'tests/Fixtures/Drupal';

    return [
      'not_set_with_drupal_core' => [NULL, $core_path, $drupal_root],
      'not_set_without_drupal_core' => [NULL, NULL, NULL],
      'empty_with_drupal_core' => ['', $core_path, $drupal_root],
      'blank_with_drupal_core' => ['  ', $core_path, $drupal_root],
      'true_with_drupal_core' => [TRUE, $core_path, $drupal_root],
      'array_with_drupal_core' => [[$drupal_root], $core_path, $drupal_root],
      'not_set_with_non_normalized_core_path' => [NULL, 'tests/Fixtures/Drupal/sites/../core', $drupal_root],
      'not_set_with_core_outside_drupal_root' => [NULL, 'tests/Fixtures/Inheritance/core', NULL],
      'not_set_with_missing_core_directory' => [NULL, 'tests/Fixtures/Missing/core', NULL],
      'false_with_drupal_core' => [FALSE, $core_path, NULL],
      'relative_path' => [$drupal_root, NULL, $drupal_root],
      'relative_path_with_trailing_slash' => [$drupal_root . '/', NULL, $drupal_root],
      'absolute_path' => [dirname(__DIR__, 2) . '/' . $drupal_root, NULL, $drupal_root],
      'path_with_core_outside_drupal_root' => [$drupal_root, 'tests/Fixtures/Inheritance/core', $drupal_root],
    ];
  }

  /**
   * Test that detection is skipped when the setting decides the root.
   *
   * @param mixed $setting
   *   The 'drupalRoot' property value.
   * @param string|null $expected
   *   The expected root relative to the project root, or NULL.
   */
  #[DataProvider('dataProviderResolveWithoutDetection')]
  public function testResolveWithoutDetection(mixed $setting, ?string $expected): void {
    $resolver = new DrupalRootResolver(static fn(): ?string => throw new \LogicException('Drupal core must not be looked up.'));

    $this->assertSame($expected === NULL ? NULL : realpath($expected), $resolver->resolve($setting));
  }

  /**
   * Data provider for testResolveWithoutDetection.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderResolveWithoutDetection(): array {
    return [
      'false' => [FALSE, NULL],
      'path' => ['tests/Fixtures/Drupal', 'tests/Fixtures/Drupal'],
    ];
  }

  /**
   * Test that a path without Drupal core is reported.
   *
   * @param string $setting
   *   The 'drupalRoot' property value.
   */
  #[DataProvider('dataProviderResolveInvalidPath')]
  public function testResolveInvalidPath(string $setting): void {
    $resolver = new DrupalRootResolver(static fn(): ?string => throw new \LogicException('Drupal core must not be looked up.'));

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage(sprintf('Invalid drupalRoot "%s": core/lib/Drupal.php not found. Relative paths resolve from %s.', $setting, getcwd()));

    $resolver->resolve($setting);
  }

  /**
   * Data provider for testResolveInvalidPath.
   *
   * @return array<string, array<string>>
   *   Test cases.
   */
  public static function dataProviderResolveInvalidPath(): array {
    return [
      'directory_without_drupal_core' => ['tests/Fixtures'],
      'drupal_core_directory' => ['tests/Fixtures/Drupal/core'],
      'missing_directory' => ['tests/Fixtures/Missing'],
      'file' => ['tests/Fixtures/Valid.php'],
      'absolute_directory_without_drupal_core' => [dirname(__DIR__, 2) . '/tests/Fixtures'],
    ];
  }

  /**
   * Test detection with Composer's installed packages.
   *
   * This project does not install Drupal core.
   */
  public function testResolveWithInstalledPackages(): void {
    $this->assertNull((new DrupalRootResolver())->resolve(NULL));
  }

  /**
   * Test finding the install path of a Composer package.
   *
   * @param string $package_name
   *   The package name.
   * @param string|null $expected
   *   The expected install path relative to the project root, or NULL.
   */
  #[DataProvider('dataProviderFindInstallPath')]
  public function testFindInstallPath(string $package_name, ?string $expected): void {
    $path = (new \ReflectionMethod(DrupalRootResolver::class, 'findInstallPath'))->invoke(NULL, $package_name);

    $this->assertSame($expected === NULL ? NULL : realpath($expected), is_string($path) ? realpath($path) : $path);
  }

  /**
   * Data provider for testFindInstallPath.
   *
   * @return array<string, array<string|null>>
   *   Test cases.
   */
  public static function dataProviderFindInstallPath(): array {
    return [
      'installed_package' => ['squizlabs/php_codesniffer', 'vendor/squizlabs/php_codesniffer'],
      'drupal_core' => ['drupal/core', NULL],
      'missing_package' => ['vendor/missing', NULL],
    ];
  }

}
