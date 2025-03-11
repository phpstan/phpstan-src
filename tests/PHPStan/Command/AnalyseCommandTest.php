<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function array_merge;
use function chdir;
use function getcwd;
use function microtime;
use function realpath;
use function rename;
use function sprintf;
use function unlink;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * @group exec
 */
class AnalyseCommandTest extends PHPStanTestCase
{

	/**
	 * @dataProvider autoDiscoveryPathsProvider
	 */
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

	public function testGenerateBaselineIgnoreNewErrorsRemoveFile(): void
	{
		$baselineFile = __DIR__ . '/data-ignore-new-errors/baseline.neon';
		$this->runCommand(0, [
			'paths' => [__DIR__ . '/data-ignore-new-errors/A.php', __DIR__ . '/data-ignore-new-errors/B.php'],
			'--configuration' => __DIR__ . '/data-ignore-new-errors/empty.neon',
			'--level' => '9',
			'--generate-baseline' => $baselineFile,
		]);

		$output = $this->runCommand(0, [
			'paths' => [__DIR__ . '/data-ignore-new-errors/B.php', __DIR__ . '/data-ignore-new-errors/C.php'],
			'--configuration' => $baselineFile,
			'--level' => '9',
			'--generate-baseline' => $baselineFile,
			'--ignore-new-errors' => true,
		]);
		@unlink($baselineFile);

		$this->assertStringContainsString('[OK] Baseline generated with 1 error', $output);
	}

	public function testGenerateBaselineIgnoreNewErrorsChangeFile(): void
	{
		$baselineFile = __DIR__ . '/data-ignore-new-errors-baseline/baseline.neon';
		$baselineFileSecondRun = __DIR__ . '/data-ignore-new-errors/baseline.neon';
		$this->runCommand(0, [
			'paths' => [__DIR__ . '/data-ignore-new-errors-baseline/A.php'],
			'--configuration' => __DIR__ . '/data-ignore-new-errors-baseline/empty.neon',
			'--level' => '9',
			'--generate-baseline' => $baselineFile,
		]);

		rename($baselineFile, $baselineFileSecondRun);
		$output = $this->runCommand(0, [
			'paths' => [__DIR__ . '/data-ignore-new-errors/A.php'],
			'--configuration' => $baselineFileSecondRun,
			'--level' => '9',
			'--generate-baseline' => $baselineFileSecondRun,
			'--ignore-new-errors' => true,
		]);
		@unlink($baselineFileSecondRun);

		$this->assertStringContainsString('[OK] Baseline generated with 2 errors', $output);
	}

	public function testGenerateBaselineIgnoreNewErrorsEmptyBaseline(): void
	{
		$baselineFile = __DIR__ . '/data-ignore-new-errors/baseline.neon';
		$this->runCommand(0, [
			'paths' => [__DIR__ . '/data-ignore-new-errors/A.php', __DIR__ . '/data-ignore-new-errors/B.php'],
			'--configuration' => __DIR__ . '/data-ignore-new-errors/empty.neon',
			'--level' => '9',
			'--generate-baseline' => $baselineFile,
		]);

		$output = $this->runCommand(1, [
			'paths' => [__DIR__ . '/data-ignore-new-errors/C.php'],
			'--configuration' => $baselineFile,
			'--level' => '9',
			'--generate-baseline' => $baselineFile,
			'--ignore-new-errors' => true,
		]);
		@unlink($baselineFile);

		$this->assertStringContainsString('[ERROR] No errors were found during the analysis. Baseline could not be generated.', $output);
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

		$commandTester->execute(array_merge([
			'paths' => [__DIR__ . DIRECTORY_SEPARATOR . 'test'],
			'--debug' => true,
		], $parameters), ['debug' => true]);

		$this->assertSame($expectedStatusCode, $commandTester->getStatusCode(), $commandTester->getDisplay());

		return $commandTester->getDisplay();
	}

}
