<?php declare(strict_types = 1);

namespace PHPStan\Process;

use PHPStan\Command\AnalyseCommand;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use function getenv;
use function putenv;
use function sprintf;

#[CoversNothing]
class ProcessHelperTest extends TestCase
{

	public function testWorkerCommandDisablesPcov(): void
	{
		if (!PcovHelper::shouldDisableInSubProcesses()) {
			$this->markTestSkipped('pcov is not loaded in this process.');
		}

		$this->assertStringContainsString(
			sprintf('-d %s', PcovHelper::DISABLED_INI_SETTING),
			self::getWorkerCommand(),
		);
	}

	public function testWorkerCommandKeepsPcovWhenAllowed(): void
	{
		if (!PcovHelper::isLoaded()) {
			$this->markTestSkipped('pcov is not loaded in this process.');
		}

		$originalValue = getenv(PcovHelper::ALLOW_ENV_VARIABLE);
		putenv(sprintf('%s=1', PcovHelper::ALLOW_ENV_VARIABLE));

		try {
			$this->assertStringNotContainsString('pcov', self::getWorkerCommand());
		} finally {
			putenv($originalValue === false
				? PcovHelper::ALLOW_ENV_VARIABLE
				: sprintf('%s=%s', PcovHelper::ALLOW_ENV_VARIABLE, $originalValue));
		}
	}

	public function testWorkerCommandDoesNotMentionPcovWhenItIsNotLoaded(): void
	{
		if (PcovHelper::isLoaded()) {
			$this->markTestSkipped('pcov is loaded in this process.');
		}

		$this->assertStringNotContainsString('pcov', self::getWorkerCommand());
	}

	private static function getWorkerCommand(): string
	{
		return ProcessHelper::getWorkerCommand(
			'bin/phpstan',
			'worker',
			null,
			[],
			self::createInput(),
		);
	}

	private static function createInput(): InputInterface
	{
		return new ArrayInput(['paths' => ['src']], new InputDefinition([
			new InputArgument('paths', InputArgument::IS_ARRAY),
			new InputOption(AnalyseCommand::OPTION_LEVEL, mode: InputOption::VALUE_REQUIRED),
			new InputOption('autoload-file', mode: InputOption::VALUE_REQUIRED),
			new InputOption('memory-limit', mode: InputOption::VALUE_REQUIRED),
			new InputOption('xdebug', mode: InputOption::VALUE_NONE),
			new InputOption('verbose', mode: InputOption::VALUE_NONE),
		]));
	}

}
