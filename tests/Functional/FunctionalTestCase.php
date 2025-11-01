<?php

declare(strict_types=1);

namespace DrevOps\PhpcsStandard\Tests\Functional;

use AlexSkrypnyk\PhpunitHelpers\Traits\AssertArrayTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\LocationsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\ProcessTrait;
use PHPUnit\Framework\TestCase;

/**
 * Base class for functional tests that run phpcs commands.
 *
 * Provides shared helper methods for executing phpcs and parsing results.
 */
abstract class FunctionalTestCase extends TestCase {

  use LocationsTrait;
  use ProcessTrait;
  use AssertArrayTrait;

  /**
   * The sniff source pattern to filter violations.
   *
   * Child classes should override this to specify which sniff to test.
   */
  protected string $sniffSource = 'DrevOps.NamingConventions.';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    self::locationsInit();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Clean up test directories.
    self::locationsTearDown();

    // Stop any running processes.
    $this->processTearDown();

    parent::tearDown();
  }

  /**
   * Run phpcs against a file and return parsed JSON results.
   *
   * @param string $file_path
   *   The path to the file to check.
   * @param array<int, array<string, mixed>> $expected_violations
   *   Array of violation structures that should be present.
   *   Each violation should contain keys like 'message', 'line', 'source', etc.
   *   Will match against actual violations in the JSON output.
   */
  protected function runPhpcs(string $file_path, array $expected_violations = []): void {
    $phpcs_bin = __DIR__ . '/../../vendor/bin/phpcs';
    $this->assertFileExists($phpcs_bin, 'PHPCS binary must exist');

    $this->assertFileExists($file_path, 'File to check must exist');

    // Run phpcs using ProcessTrait.
    $this->processRun(
      $phpcs_bin,
      ['--standard=DrevOps', '--report=json', $file_path],
      timeout: 120
    );

    // @phpstan-ignore-next-line
    $output = $this->process->getOutput() . $this->process->getErrorOutput();
    $violations = $this->getPhpcsViolations($output);

    // Note: We don't assert on process exit code because we're filtering
    // violations by sniff. PHPCS may return non-zero due to violations from
    // other sniffs in the DrevOps standard.
    // Normalize both arrays to remove fields that can change.
    $normalized_expected = $this->normalizeViolations($expected_violations);
    $normalized_actual = $this->normalizeViolations($violations);

    $this->assertEquals($normalized_expected, $normalized_actual, 'Expected violations should be present in PHPCS output');
  }

  /**
   * Normalize violations by removing fields that are not essential for testing.
   *
   * Removes severity, type, line, and column as these can change frequently
   * and are not critical for verifying sniff behavior.
   *
   * @param array<int, array<string, mixed>> $violations
   *   Array of violations to normalize.
   *
   * @return array<int, array<string, mixed>>
   *   Normalized violations with only message, source, and fixable.
   */
  protected function normalizeViolations(array $violations): array {
    return array_map(function (array $violation): array {
      return [
        'message' => $violation['message'] ?? '',
        'source' => $violation['source'] ?? '',
        'fixable' => $violation['fixable'] ?? FALSE,
      ];
    }, $violations);
  }

  protected function getPhpcsViolations(string $output): array {
    $result = json_decode($output, TRUE);
    $this->assertIsArray($result, 'PHPCS output should be valid JSON');

    $this->assertArrayHasKey('files', $result);
    // @phpstan-ignore-next-line
    $file_results = reset($result['files']);
    $this->assertIsArray($file_results, 'File results should be an array');

    $violations = isset($file_results['messages']) && is_array($file_results['messages']) ? $file_results['messages'] : [];

    $filtered = array_filter($violations, function (mixed $violation): bool {
      return is_array($violation) && isset($violation['source']) && is_string($violation['source']) && str_contains($violation['source'], $this->sniffSource);
    });

    // Re-index array to start from 0 for easier comparison.
    return array_values($filtered);
  }

  /**
   * Returns suffix for assertion messages.
   *
   * Required by ProcessTrait for assertion context.
   *
   * @return string
   *   Empty string as we don't need custom suffixes.
   */
  protected function assertionSuffix(): string {
    return '';
  }

}
