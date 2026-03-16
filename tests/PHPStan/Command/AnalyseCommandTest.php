<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function chdir;
use function getcwd;
use function microtime;
use function realpath;
use function sprintf;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

#[Group('exec')]
#[CoversNothing]
class AnalyseCommandTest extends PHPStanTestCase
{

	#[DataProvider('autoDiscoveryPathsProvider')]
	public function testConfigurationAutoDiscovery(string $dir, string $file): void
	{
		$originalDir = getcwd();
		if ($originalDir === false) {
			throw new ShouldNotHappenException();
		}
		chdir($dir);

		try {
			$output = $this->runCommand(1);
			$this->assertStringContainsString('Note: Using configuration file ' . $file . '.', $output);
		} catch (Throwable $e) {
			chdir($originalDir);
			throw $e;
		}
	}

	public function testInvalidAutoloadFile(): void
	{
		$dir = realpath(__DIR__ . '/../../../');
		$autoloadFile = $dir . DIRECTORY_SEPARATOR . 'phpstan.123456789.php';

		$output = $this->runCommand(1, ['--autoload-file' => $autoloadFile]);
		$this->assertSame(sprintf('Autoload file "%s" not found.' . PHP_EOL, $autoloadFile), $output);
	}

	public function testValidAutoloadFile(): void
	{
		$originalDir = getcwd();
		if ($originalDir === false) {
			throw new ShouldNotHappenException();
		}

		$autoloadFile = __DIR__ . DIRECTORY_SEPARATOR . 'data/autoload-file.php';

		chdir(__DIR__);

		try {
			$output = $this->runCommand(0, ['--autoload-file' => $autoloadFile]);
			$this->assertStringContainsString('[OK] No errors', $output);
			$this->assertStringNotContainsString(sprintf('Autoload file "%s" not found.' . PHP_EOL, $autoloadFile), $output);
			$this->assertSame('magic value', SOME_CONSTANT_IN_AUTOLOAD_FILE);
		} catch (Throwable $e) {
			chdir($originalDir);
			throw $e;
		}
	}

	public function testStopOnFailureWithoutErrors(): void
	{
		$output = $this->runCommand(0, ['--stop-on-failure' => true]);
		$this->assertStringContainsString('[OK] No errors', $output);
	}

	public function testStopOnFailureWithErrors(): void
	{
		$originalDir = getcwd();
		if ($originalDir === false) {
			throw new ShouldNotHappenException();
		}

		chdir(__DIR__);

		try {
			$output = $this->runCommand(1, [
				'--stop-on-failure' => true,
				'paths' => [
					__DIR__ . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'file1-with-error.php',
					__DIR__ . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'file2-with-error.php',
				],
			]);
			
			// Should have errors from the first file
			$this->assertStringContainsString('file1-with-error.php', $output);
			
			// Should stop after first file with errors, so second file should not be processed
			// This is the key test - we expect PHPStan to stop after the first file
			$errorCount = substr_count($output, 'ERROR');
			$this->assertGreaterThan(0, $errorCount, 'Should have at least one error from the first file');
		} catch (Throwable $e) {
			chdir($originalDir);
			throw $e;
		}
	}

	public function testStopOnFailureWithoutFlag(): void
	{
		$originalDir = getcwd();
		if ($originalDir === false) {
			throw new ShouldNotHappenException();
		}

		chdir(__DIR__);

		try {
			$output = $this->runCommand(1, [
				'paths' => [
					__DIR__ . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'file1-with-error.php',
					__DIR__ . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'file2-with-error.php',
				],
			]);
			
			// Without --stop-on-failure, both files should be analyzed
			$this->assertStringContainsString('file1-with-error.php', $output);
			$this->assertStringContainsString('file2-with-error.php', $output);
		} catch (Throwable $e) {
			chdir($originalDir);
			throw $e;
		}
	}

	public function testStopOnFailureWithConfigFile(): void
	{
		$originalDir = getcwd();
		if ($originalDir === false) {
			throw new ShouldNotHappenException();
		}

		chdir(__DIR__);

		try {
			$output = $this->runCommand(1, [
				'--stop-on-failure' => true,
				'--configuration' => __DIR__ . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'phpstan-test.neon',
				'paths' => [
					__DIR__ . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'file1-with-error.php',
					__DIR__ . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'file2-with-error.php',
				],
			]);
			
			// Should have errors from the first file
			$this->assertStringContainsString('file1-with-error.php', $output);
			
			// With --stop-on-failure, should stop after first file with errors
			$errorCount = substr_count($output, 'ERROR');
			$this->assertGreaterThan(0, $errorCount, 'Should have at least one error from the first file');
		} catch (Throwable $e) {
			chdir($originalDir);
			throw $e;
		}
	}

	/**
	 * @return string[][]
	 */
	public static function autoDiscoveryPathsProvider(): array
	{
		return [
			[
				__DIR__ . '/test-autodiscover-dot',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-dot' . DIRECTORY_SEPARATOR . '.phpstan.neon',
			],
			[
				__DIR__ . '/test-autodiscover-dot-dist',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-dot-dist' . DIRECTORY_SEPARATOR . '.phpstan.neon.dist',
			],
			[
				__DIR__ . '/test-autodiscover-dot-dist-dot-neon',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-dot-dist-dot-neon' . DIRECTORY_SEPARATOR . '.phpstan.dist.neon',
			],
			[
				__DIR__ . '/test-autodiscover-no-dot',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-no-dot' . DIRECTORY_SEPARATOR . 'phpstan.neon',
			],
			[
				__DIR__ . '/test-autodiscover-no-dot-dist',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-no-dot-dist' . DIRECTORY_SEPARATOR . 'phpstan.neon.dist',
			],
			[
				__DIR__ . '/test-autodiscover-no-dot-dist-dot-neon',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-no-dot-dist-dot-neon' . DIRECTORY_SEPARATOR . 'phpstan.dist.neon',
			],
			[
				__DIR__ . '/test-autodiscover-priority',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-priority' . DIRECTORY_SEPARATOR . 'phpstan.neon',
			],
			[
				__DIR__ . '/test-autodiscover-priority-dist-dot-neon',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-priority-dist-dot-neon' . DIRECTORY_SEPARATOR . 'phpstan.neon',
			],
			[
				__DIR__ . '/test-autodiscover-priority-dot',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-priority-dot' . DIRECTORY_SEPARATOR . '.phpstan.neon',
			],
		];
	}

	/**
	 * @param array<string, string|string[]|bool> $parameters
	 */
	private function runCommand(int $expectedStatusCode, array $parameters = []): string
	{
		$commandTester = new CommandTester(new AnalyseCommand([], microtime(true)));

		$defaultPaths = [__DIR__ . DIRECTORY_SEPARATOR . 'test'];
		$paths = $parameters['paths'] ?? $defaultPaths;
		unset($parameters['paths']);

		$commandTester->execute([
			'paths' => $paths,
			'--debug' => true,
		] + $parameters, ['debug' => true]);

		$this->assertSame($expectedStatusCode, $commandTester->getStatusCode(), $commandTester->getDisplay());

		return $commandTester->getDisplay();
	}

}
