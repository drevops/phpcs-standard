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

    // Assert process success/failure based on expectation.
    // PHPCS returns 0 for no violations, non-zero for violations.
    if (!empty($expected_violations)) {
      $this->assertProcessFailed('Expected phpcs to find violations');
    }
    else {
      $this->assertProcessSuccessful('Expected phpcs to pass without violations');
    }

    // @phpstan-ignore-next-line
    $output = $this->process->getOutput() . $this->process->getErrorOutput();
    $violations = $this->getPhpcsViolations($output);

    $this->assertEquals($expected_violations, $violations, 'Expected violations should be present in PHPCS output');
  }

  protected function getPhpcsViolations(string $output): array {
    $result = json_decode($output, TRUE);
    $this->assertIsArray($result, 'PHPCS output should be valid JSON');

    $this->assertArrayHasKey('files', $result);
    // @phpstan-ignore-next-line
    $file_results = reset($result['files']);
    $this->assertIsArray($file_results, 'File results should be an array');

    $violations = isset($file_results['messages']) && is_array($file_results['messages']) ? $file_results['messages'] : [];

    return array_filter($violations, function (mixed $violation): bool {
      return is_array($violation) && isset($violation['source']) && is_string($violation['source']) && str_contains($violation['source'], 'DrevOps.NamingConventions.VariableSnakeCase');
    });
  }

}
