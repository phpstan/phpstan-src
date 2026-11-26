<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function chdir;
use function getcwd;
use function getenv;
use function microtime;
use function putenv;
use function realpath;
use function sprintf;
use function sys_get_temp_dir;
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

	public function testSummaryIsPrintedToStdOutputInCiWithTableFormat(): void
	{
		[$stdOutput, $errorOutput] = $this->runCommandInGithubActions('table');
		$this->assertStringContainsString('[OK] No errors', $stdOutput);
		$this->assertStringContainsString('PHPStan version:', $stdOutput);
		$this->assertStringContainsString('Elapsed time:', $stdOutput);
		$this->assertStringContainsString('Peak memory:', $stdOutput);
		$this->assertStringNotContainsString('PHPStan version:', $errorOutput);
		$this->assertStringNotContainsString('Elapsed time:', $errorOutput);
		$this->assertStringNotContainsString('Peak memory:', $errorOutput);
	}

	public function testSummaryIsPrintedToErrorOutputInCiWithMachineReadableFormat(): void
	{
		[$stdOutput, $errorOutput] = $this->runCommandInGithubActions('json');
		$this->assertStringNotContainsString('PHPStan version:', $stdOutput);
		$this->assertStringNotContainsString('Elapsed time:', $stdOutput);
		$this->assertStringNotContainsString('Peak memory:', $stdOutput);
		$this->assertStringContainsString('PHPStan version:', $errorOutput);
		$this->assertStringContainsString('Elapsed time:', $errorOutput);
		$this->assertStringContainsString('Peak memory:', $errorOutput);
	}

	public function testSummaryIsPrintedToStdOutputInCiWhenGeneratingBaseline(): void
	{
		[$stdOutput, $errorOutput] = $this->runCommandInGithubActions('json', 1, ['--generate-baseline' => sys_get_temp_dir() . '/phpstan-summary-baseline.neon']);
		$this->assertStringContainsString('To allow generating empty baselines', $stdOutput);
		$this->assertStringContainsString('Elapsed time:', $stdOutput);
		$this->assertStringNotContainsString('Elapsed time:', $errorOutput);
	}

	/**
	 * @param array<string, string> $parameters
	 * @return array{string, string}
	 */
	private function runCommandInGithubActions(string $errorFormat, int $expectedStatusCode = 0, array $parameters = []): array
	{
		$originalDir = getcwd();
		if ($originalDir === false) {
			throw new ShouldNotHappenException();
		}
		$originalGithubActions = getenv('GITHUB_ACTIONS');

		chdir(__DIR__);
		putenv('GITHUB_ACTIONS=true');

		try {
			$commandTester = new CommandTester(new AnalyseCommand([], microtime(true)));
			$commandTester->execute([
				'paths' => [__DIR__ . DIRECTORY_SEPARATOR . 'test'],
				'--debug' => true,
				'--error-format' => $errorFormat,
			] + $parameters, ['verbosity' => OutputInterface::VERBOSITY_DEBUG, 'capture_stderr_separately' => true]);

			$this->assertSame($expectedStatusCode, $commandTester->getStatusCode(), $commandTester->getDisplay() . $commandTester->getErrorOutput());

			return [$commandTester->getDisplay(), $commandTester->getErrorOutput()];
		} finally {
			chdir($originalDir);
			putenv($originalGithubActions === false ? 'GITHUB_ACTIONS' : 'GITHUB_ACTIONS=' . $originalGithubActions);
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
	 * @param array<string, string> $parameters
	 */
	private function runCommand(int $expectedStatusCode, array $parameters = []): string
	{
		$commandTester = new CommandTester(new AnalyseCommand([], microtime(true)));

		$commandTester->execute([
			'paths' => [__DIR__ . DIRECTORY_SEPARATOR . 'test'],
			'--debug' => true,
		] + $parameters, ['debug' => true]);

		$this->assertSame($expectedStatusCode, $commandTester->getStatusCode(), $commandTester->getDisplay());

		return $commandTester->getDisplay();
	}

}
