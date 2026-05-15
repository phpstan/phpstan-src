<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Override;
use PHPStan\File\PathNotFoundException;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function is_array;
use function is_bool;
use function is_string;

final class FixerWorkerCommand extends Command
{

	private const NAME = 'fixer:worker';

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
			->setDescription('(Internal) Support for PHPStan Pro.')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('memory-limit', mode: InputOption::VALUE_REQUIRED, description: 'Memory limit for analysis'),
				new InputOption('xdebug', mode: InputOption::VALUE_NONE, description: 'Allow running with Xdebug for debugging purposes'),
				new InputOption('server-port', mode: InputOption::VALUE_REQUIRED, description: 'Server port for FixerApplication'),
			])
			->setHidden(true);
	}

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$paths = $input->getArgument('paths');
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(AnalyseCommand::OPTION_LEVEL);
		$allowXdebug = $input->getOption('xdebug');
		$serverPort = $input->getOption('server-port');

		if (
			!is_array($paths)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
			|| (!is_bool($allowXdebug))
			|| (!is_string($serverPort))
		) {
			throw new ShouldNotHappenException();
		}

		try {
			$inceptionResult = CommandHelper::begin(
				$input,
				$output,
				$paths,
				$memoryLimit,
				$autoloadFile,
				$this->composerAutoloaderProjectPaths,
				$configuration,
				null,
				$level,
				$allowXdebug,
				false,
				null,
				null,
				false,
			);
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		$container = $inceptionResult->getContainer();

		try {
			[$inceptionFiles, $isOnlyFiles] = $inceptionResult->getFiles();
		} catch (PathNotFoundException | InceptionNotSuccessfulException) {
			throw new ShouldNotHappenException();
		}

		// Everything after the boot lives in FixerWorkerRunner so a
		// pcntl_fork()-ed child can reuse it without re-booting (see
		// FixerApplication).
		$fixerWorkerRunner = $container->getByType(FixerWorkerRunner::class);

		return $fixerWorkerRunner->run(
			$inceptionResult->getErrorOutput(),
			$inceptionFiles,
			$isOnlyFiles,
			$inceptionResult->getProjectConfigArray(),
			$configuration,
			(int) $serverPort,
			$input,
		);
	}

}
