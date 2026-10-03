<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Unit;

use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use DrevOps\Helpers\DrupalNamespaceMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DrupalNamespaceMap.
 *
 * Each test builds its Drupal root in a temporary workspace.
 */
#[CoversClass(DrupalNamespaceMap::class)]
class DrupalNamespaceMapTest extends TestCase {

  use LocationsTrait;

  /**
   * The Drupal root of the test.
   */
  protected string $drupalRoot;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->locationsInit();
    $this->drupalRoot = static::locationsMkdir(static::$tmp . DIRECTORY_SEPARATOR . 'drupal');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->locationsTearDown();

    parent::tearDown();
  }

  /**
   * Test finding the source file of a class.
   *
   * @param array<int, string> $files
   *   Files to create, relative to the Drupal root.
   * @param string $class_name
   *   The class name to look up.
   * @param string|null $expected
   *   The expected path relative to the Drupal root, or NULL.
   */
  #[DataProvider('dataProviderFindFile')]
  public function testFindFile(array $files, string $class_name, ?string $expected): void {
    $this->createFiles($files);

    $this->assertSame($expected === NULL ? NULL : $this->drupalRoot . '/' . $expected, (new DrupalNamespaceMap($this->drupalRoot))->findFile($class_name));
  }

  /**
   * Data provider for testFindFile.
   *
   * @return array<string, array<mixed>>
   *   Test cases.
   */
  public static function dataProviderFindFile(): array {
    $views = 'core/modules/views/views.info.yml';

    return [
      'core_module' => [
        [$views, 'core/modules/views/src/ViewExecutable.php'],
        'Drupal\views\ViewExecutable',
        'core/modules/views/src/ViewExecutable.php',
      ],
      'core_module_nested_namespace' => [
        [$views, 'core/modules/views/src/Plugin/views/field/EntityField.php'],
        'Drupal\views\Plugin\views\field\EntityField',
        'core/modules/views/src/Plugin/views/field/EntityField.php',
      ],
      'core_module_test_class' => [
        [$views, 'core/modules/views/tests/src/Kernel/Handler/FieldKernelTestBase.php'],
        'Drupal\Tests\views\Kernel\Handler\FieldKernelTestBase',
        'core/modules/views/tests/src/Kernel/Handler/FieldKernelTestBase.php',
      ],
      'core_module_test_module' => [
        [
          $views,
          'core/modules/views/tests/modules/views_test_data/views_test_data.info.yml',
          'core/modules/views/tests/modules/views_test_data/src/ViewsTestData.php',
        ],
        'Drupal\views_test_data\ViewsTestData',
        'core/modules/views/tests/modules/views_test_data/src/ViewsTestData.php',
      ],
      'core_module_named_config' => [
        ['core/modules/config/config.info.yml', 'core/modules/config/src/ConfigSubscriber.php'],
        'Drupal\config\ConfigSubscriber',
        'core/modules/config/src/ConfigSubscriber.php',
      ],
      'core_profile' => [
        ['core/profiles/standard/standard.info.yml', 'core/profiles/standard/src/StandardInstaller.php'],
        'Drupal\standard\StandardInstaller',
        'core/profiles/standard/src/StandardInstaller.php',
      ],
      'core_theme' => [
        ['core/themes/olivero/olivero.info.yml', 'core/themes/olivero/src/OliveroPreRender.php'],
        'Drupal\olivero\OliveroPreRender',
        'core/themes/olivero/src/OliveroPreRender.php',
      ],
      'contrib_module' => [
        ['modules/contrib/token/token.info.yml', 'modules/contrib/token/src/Token.php'],
        'Drupal\token\Token',
        'modules/contrib/token/src/Token.php',
      ],
      'module_without_subdirectory' => [
        ['modules/token/token.info.yml', 'modules/token/src/Token.php'],
        'Drupal\token\Token',
        'modules/token/src/Token.php',
      ],
      'submodule' => [
        [
          'modules/contrib/webform/webform.info.yml',
          'modules/contrib/webform/modules/webform_ui/webform_ui.info.yml',
          'modules/contrib/webform/modules/webform_ui/src/WebformUiElementForm.php',
        ],
        'Drupal\webform_ui\WebformUiElementForm',
        'modules/contrib/webform/modules/webform_ui/src/WebformUiElementForm.php',
      ],
      'profile' => [
        ['profiles/custom/my_profile/my_profile.info.yml', 'profiles/custom/my_profile/src/Installer.php'],
        'Drupal\my_profile\Installer',
        'profiles/custom/my_profile/src/Installer.php',
      ],
      'module_in_profile' => [
        [
          'profiles/custom/my_profile/my_profile.info.yml',
          'profiles/custom/my_profile/modules/profile_module/profile_module.info.yml',
          'profiles/custom/my_profile/modules/profile_module/src/ProfileService.php',
        ],
        'Drupal\profile_module\ProfileService',
        'profiles/custom/my_profile/modules/profile_module/src/ProfileService.php',
      ],
      'theme' => [
        ['themes/custom/my_theme/my_theme.info.yml', 'themes/custom/my_theme/src/ThemeHelper.php'],
        'Drupal\my_theme\ThemeHelper',
        'themes/custom/my_theme/src/ThemeHelper.php',
      ],
      'site_module' => [
        [
          'sites/development.services.yml',
          'sites/default/settings.php',
          'sites/default/modules/site_module/site_module.info.yml',
          'sites/default/modules/site_module/src/SiteService.php',
        ],
        'Drupal\site_module\SiteService',
        'sites/default/modules/site_module/src/SiteService.php',
      ],
      'site_profile' => [
        ['sites/default/profiles/site_profile/site_profile.info.yml', 'sites/default/profiles/site_profile/src/Installer.php'],
        'Drupal\site_profile\Installer',
        'sites/default/profiles/site_profile/src/Installer.php',
      ],
      'site_theme' => [
        ['sites/default/themes/site_theme/site_theme.info.yml', 'sites/default/themes/site_theme/src/ThemeHelper.php'],
        'Drupal\site_theme\ThemeHelper',
        'sites/default/themes/site_theme/src/ThemeHelper.php',
      ],
      'shared_site_module' => [
        ['sites/all/modules/legacy_module/legacy_module.info.yml', 'sites/all/modules/legacy_module/src/LegacyService.php'],
        'Drupal\legacy_module\LegacyService',
        'sites/all/modules/legacy_module/src/LegacyService.php',
      ],
      'module_overrides_core_module' => [
        [
          'core/modules/duplicate/duplicate.info.yml',
          'core/modules/duplicate/src/Duplicate.php',
          'modules/contrib/duplicate/duplicate.info.yml',
          'modules/contrib/duplicate/src/Duplicate.php',
        ],
        'Drupal\duplicate\Duplicate',
        'modules/contrib/duplicate/src/Duplicate.php',
      ],
      'site_module_overrides_module' => [
        [
          'modules/contrib/duplicate/duplicate.info.yml',
          'modules/contrib/duplicate/src/Duplicate.php',
          'sites/default/modules/duplicate/duplicate.info.yml',
          'sites/default/modules/duplicate/src/Duplicate.php',
        ],
        'Drupal\duplicate\Duplicate',
        'sites/default/modules/duplicate/src/Duplicate.php',
      ],
      'overriding_module_without_class' => [
        [
          'core/modules/duplicate/duplicate.info.yml',
          'core/modules/duplicate/src/Duplicate.php',
          'modules/contrib/duplicate/duplicate.info.yml',
        ],
        'Drupal\duplicate\Duplicate',
        NULL,
      ],
      'extension_in_hidden_directory' => [
        ['modules/.hidden/hidden_module/hidden_module.info.yml', 'modules/.hidden/hidden_module/src/Hidden.php'],
        'Drupal\hidden_module\Hidden',
        NULL,
      ],
      'extension_in_node_modules' => [
        [
          'modules/contrib/token/token.info.yml',
          'modules/contrib/token/node_modules/node_dependency/node_dependency.info.yml',
          'modules/contrib/token/node_modules/node_dependency/src/Dependency.php',
        ],
        'Drupal\node_dependency\Dependency',
        NULL,
      ],
      'extension_in_fixtures' => [
        [
          $views,
          'core/modules/views/tests/fixtures/fixture_module/fixture_module.info.yml',
          'core/modules/views/tests/fixtures/fixture_module/src/Fixture.php',
        ],
        'Drupal\fixture_module\Fixture',
        NULL,
      ],
      'extension_outside_search_directories' => [
        ['libraries/library_extension/library_extension.info.yml', 'libraries/library_extension/src/Library.php'],
        'Drupal\library_extension\Library',
        NULL,
      ],
      'extension_in_site_directory' => [
        ['sites/default/site_extension/site_extension.info.yml', 'sites/default/site_extension/src/SiteExtension.php'],
        'Drupal\site_extension\SiteExtension',
        NULL,
      ],
      'extension_in_hidden_site_directory' => [
        ['sites/.hidden/modules/hidden_site_module/hidden_site_module.info.yml', 'sites/.hidden/modules/hidden_site_module/src/Hidden.php'],
        'Drupal\hidden_site_module\Hidden',
        NULL,
      ],
      'missing_class_file' => [
        [$views],
        'Drupal\views\ViewExecutable',
        NULL,
      ],
      'test_class_outside_tests_directory' => [
        [$views, 'core/modules/views/src/Kernel/Handler/FieldKernelTestBase.php'],
        'Drupal\Tests\views\Kernel\Handler\FieldKernelTestBase',
        NULL,
      ],
      'extension_class_in_tests_directory' => [
        [$views, 'core/modules/views/tests/src/ViewExecutable.php'],
        'Drupal\views\ViewExecutable',
        NULL,
      ],
      'unknown_extension' => [
        [$views, 'core/modules/views/src/ViewExecutable.php'],
        'Drupal\unknown\ViewExecutable',
        NULL,
      ],
      'core_namespace' => [
        [$views, 'core/lib/Drupal/Core/Plugin/PluginBase.php'],
        'Drupal\Core\Plugin\PluginBase',
        NULL,
      ],
      'core_test_namespace' => [
        [$views, 'core/tests/Drupal/KernelTests/KernelTestBase.php'],
        'Drupal\KernelTests\KernelTestBase',
        NULL,
      ],
      'core_tests_namespace_class' => [
        [$views, 'core/tests/Drupal/Tests/UnitTestCase.php'],
        'Drupal\Tests\UnitTestCase',
        NULL,
      ],
      'extension_namespace' => [
        [$views],
        'Drupal\views',
        NULL,
      ],
      'tests_namespace' => [
        [$views],
        'Drupal\Tests',
        NULL,
      ],
      'drupal_namespace' => [
        [$views],
        'Drupal',
        NULL,
      ],
      'other_vendor' => [
        [$views, 'core/modules/views/src/ViewExecutable.php'],
        'Vendor\views\ViewExecutable',
        NULL,
      ],
      'empty_root' => [
        [],
        'Drupal\views\ViewExecutable',
        NULL,
      ],
    ];
  }

  /**
   * Test that the scan runs once, on the first lookup that needs it.
   */
  public function testScanRunsOnceWhenNeeded(): void {
    $map = new DrupalNamespaceMap($this->drupalRoot);

    $this->assertNull($map->findFile('Vendor\Package\Service'));
    $this->assertNull($map->findFile('Drupal\first'));

    // The lookups above did not scan, so this extension is found.
    $this->createFiles(['modules/first/first.info.yml', 'modules/first/src/First.php']);
    $this->assertSame($this->drupalRoot . '/modules/first/src/First.php', $map->findFile('Drupal\first\First'));

    // The scan does not run again, so a new extension is not found.
    $this->createFiles(['modules/second/second.info.yml', 'modules/second/src/Second.php']);
    $this->assertNull($map->findFile('Drupal\second\Second'));

    // Class files of a known extension are checked on every lookup.
    $this->createFiles(['modules/first/src/Other.php']);
    $this->assertSame($this->drupalRoot . '/modules/first/src/Other.php', $map->findFile('Drupal\first\Other'));
  }

  /**
   * Test that a symlinked extension is found at its real path.
   */
  public function testSymlinkedExtension(): void {
    $target = static::locationsMkdir(static::$tmp . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'linked_module');
    static::locationsMkdir($target . '/src');
    touch($target . '/linked_module.info.yml');
    touch($target . '/src/Linked.php');
    static::locationsMkdir($this->drupalRoot . '/modules/custom');
    symlink($target, $this->drupalRoot . '/modules/custom/linked_module');

    $this->assertSame($target . '/src/Linked.php', (new DrupalNamespaceMap($this->drupalRoot))->findFile('Drupal\linked_module\Linked'));
  }

  /**
   * Test that a symlink back to a scanned directory does not loop.
   */
  public function testSymlinkCycle(): void {
    $this->createFiles(['modules/custom/my_module/my_module.info.yml', 'modules/custom/my_module/src/Service.php']);
    symlink($this->drupalRoot . '/modules', $this->drupalRoot . '/modules/custom/loop');

    $this->assertSame($this->drupalRoot . '/modules/custom/my_module/src/Service.php', (new DrupalNamespaceMap($this->drupalRoot))->findFile('Drupal\my_module\Service'));
  }

  /**
   * Test that an unreadable directory is skipped.
   */
  public function testUnreadableDirectory(): void {
    $this->createFiles([
      'modules/locked/locked_module/locked_module.info.yml',
      'modules/locked/locked_module/src/Locked.php',
      'modules/open/open_module/open_module.info.yml',
      'modules/open/open_module/src/Open.php',
    ]);
    chmod($this->drupalRoot . '/modules/locked', 0000);

    if (is_readable($this->drupalRoot . '/modules/locked')) {
      $this->markTestSkipped('The current user reads directories regardless of their permissions.');
    }

    $map = new DrupalNamespaceMap($this->drupalRoot);

    $this->assertSame($this->drupalRoot . '/modules/open/open_module/src/Open.php', $map->findFile('Drupal\open_module\Open'));
    $this->assertNull($map->findFile('Drupal\locked_module\Locked'));
  }

  /**
   * Create empty files under the Drupal root.
   *
   * @param array<int, string> $files
   *   File paths relative to the Drupal root.
   */
  protected function createFiles(array $files): void {
    foreach ($files as $file) {
      $path = $this->drupalRoot . '/' . $file;
      static::locationsMkdir(dirname($path));
      touch($path);
    }
  }

}
