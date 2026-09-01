<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\Turbo\TurboProcessRestarter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use function escapeshellarg;
use function preg_match;
use function sprintf;
use const PHP_OS_FAMILY;

final class ProcessHelperTest extends TestCase
{

	/**
	 * @return iterable<string, array{list<string>, string, list<string>}>
	 */
	public static function dataResolveWorkerIniEntries(): iterable
	{
		$opcacheArgs = ['opcache.enable=1', 'opcache.enable_cli=1'];
		yield 'linux' => [$opcacheArgs, 'Linux', ['opcache.enable=1', 'opcache.enable_cli=1', 'phpstan.restarted=1']];
		yield 'darwin' => [$opcacheArgs, 'Darwin', ['opcache.enable=1', 'opcache.enable_cli=1', 'phpstan.restarted=1']];
		yield 'windows' => [$opcacheArgs, 'Windows', ['opcache.enable=1', 'opcache.enable_cli=1', 'opcache.cache_id=phpstan-4242-7', 'phpstan.restarted=1']];
		yield 'linux without OPcache' => [[], 'Linux', ['phpstan.restarted=1']];
		yield 'windows without OPcache' => [[], 'Windows', ['phpstan.restarted=1']];
	}

	/**
	 * @param list<string> $opcacheArgs
	 * @param list<string> $expected
	 */
	#[DataProvider('dataResolveWorkerIniEntries')]
	public function testResolveWorkerIniEntries(array $opcacheArgs, string $osFamily, array $expected): void
	{
		$this->assertSame($expected, ProcessHelper::resolveWorkerIniEntries($opcacheArgs, $osFamily, 4242, 7));
	}

	public function testWorkerCommandCarriesTheIniEntries(): void
	{
		$command = ProcessHelper::getWorkerCommand('bin/phpstan', 'worker', null, ['--port', '1234'], $this->createInput());
		$commandAgain = ProcessHelper::getWorkerCommand('bin/phpstan', 'worker', null, ['--port', '1234'], $this->createInput());

		$opcacheArgs = TurboProcessRestarter::getOpcacheArgs();
		foreach ($opcacheArgs as $opcacheArg) {
			$this->assertStringContainsString(sprintf(' -d %s ', $opcacheArg), $command);
		}

		// the restart marker is the last -d, right before the script
		$this->assertStringContainsString(
			sprintf(' -d %s=1 %s %s ', TurboProcessRestarter::RESTARTED_INI, escapeshellarg('bin/phpstan'), escapeshellarg('worker')),
			$command,
		);

		if (PHP_OS_FAMILY !== 'Windows' || $opcacheArgs === []) {
			$this->assertStringNotContainsString('opcache.cache_id=', $command);
			return;
		}

		// every Windows worker gets its own OPcache instance
		$this->assertSame(1, preg_match('~ -d opcache\.cache_id=(phpstan-\d+-\d+) ~', $command, $matches));
		$this->assertSame(1, preg_match('~ -d opcache\.cache_id=(phpstan-\d+-\d+) ~', $commandAgain, $matchesAgain));
		$this->assertNotSame($matches[1], $matchesAgain[1]);
	}

	private function createInput(): ArrayInput
	{
		return new ArrayInput(['paths' => ['src']], new InputDefinition([
			new InputArgument('paths', InputArgument::IS_ARRAY),
			new InputOption('level', 'l', InputOption::VALUE_REQUIRED),
			new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED),
			new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED),
			new InputOption('xdebug', null, InputOption::VALUE_NONE),
			new InputOption('verbose', 'v', InputOption::VALUE_NONE),
		]));
	}

}
