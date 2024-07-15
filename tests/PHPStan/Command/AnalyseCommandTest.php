<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;
use function chdir;
use function getcwd;
use function microtime;
use function realpath;
use function sprintf;
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

	/**
	 * @return string[][]
	 */
	public static function autoDiscoveryPathsProvider(): array
	{
		return [
			[
				__DIR__ . '/test-autodiscover',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover' . DIRECTORY_SEPARATOR . 'phpstan.neon',
			],
			[
				__DIR__ . '/test-autodiscover-dist',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-dist' . DIRECTORY_SEPARATOR . 'phpstan.neon.dist',
			],
			[
				__DIR__ . '/test-autodiscover-dist-dot-neon',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-dist-dot-neon' . DIRECTORY_SEPARATOR . 'phpstan.dist.neon',
			],
			[
				__DIR__ . '/test-autodiscover-priority',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-priority' . DIRECTORY_SEPARATOR . 'phpstan.neon',
			],
			[
				__DIR__ . '/test-autodiscover-priority-dist-dot-neon',
				__DIR__ . DIRECTORY_SEPARATOR . 'test-autodiscover-priority-dist-dot-neon' . DIRECTORY_SEPARATOR . 'phpstan.neon',
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
