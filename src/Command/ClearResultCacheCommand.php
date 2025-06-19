<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Override;
use PHPStan\Analyser\ResultCache\ResultCacheClearer;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function is_bool;
use function is_string;

final class ClearResultCacheCommand extends Command
{

	private const NAME = 'clear-result-cache';

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private array $composerAutoloaderProjectPaths,
	)
	{
		parent::__construct();
	}

	#[Override]
	protected function configure(): void
	{
		$this->setName(self::NAME)
			->setDescription('Clears the result cache.')
			->setDefinition([
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('debug', mode: InputOption::VALUE_NONE, description: 'Show debug information - which file is analysed, do not catch internal errors'),
				new InputOption('memory-limit', mode: InputOption::VALUE_REQUIRED, description: 'Memory limit for clearing result cache'),
				new InputOption('xdebug', mode: InputOption::VALUE_NONE, description: 'Allow running with Xdebug for debugging purposes'),
			]);
	}

	#[Override]
	protected function initialize(InputInterface $input, OutputInterface $output): void
	{
		if ((bool) $input->getOption('debug')) {
			$application = $this->getApplication();
			if ($application === null) {
				throw new ShouldNotHappenException();
			}
			$application->setCatchExceptions(false);
			return;
		}
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$memoryLimit = $input->getOption('memory-limit');
		$debugEnabled = (bool) $input->getOption('debug');
		$allowXdebug = $input->getOption('xdebug');

		if (
			(!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_bool($allowXdebug))
		) {
			throw new ShouldNotHappenException();
		}

		try {
			$inceptionResult = CommandHelper::begin(
				$input,
				$output,
				[],
				$memoryLimit,
				$autoloadFile,
				$this->composerAutoloaderProjectPaths,
				$configuration,
				null,
				'0',
				$allowXdebug,
				$debugEnabled,
				null,
				null,
				true,
			);
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		$container = $inceptionResult->getContainer();

		$resultCacheClearer = $container->getByType(ResultCacheClearer::class);
		$path = $resultCacheClearer->clear();

		$output->writeln('<info>Result cache cleared from directory:</info>');
		$output->writeln($path);

		return 0;
	}

}
