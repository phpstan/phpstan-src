<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Override;
use PHPStan\Cache\ArenaCache;
use PHPStan\File\PathNotFoundException;
use PHPStan\Parallel\WorkerRunner;
use PHPStan\Parser\PathRoutingParser;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function is_array;
use function is_bool;
use function is_string;
use function sprintf;

final class WorkerCommand extends Command
{

	private const NAME = 'worker';

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
			->setDescription('(Internal) Support for parallel analysis.')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('memory-limit', mode: InputOption::VALUE_REQUIRED, description: 'Memory limit for analysis'),
				new InputOption('xdebug', mode: InputOption::VALUE_NONE, description: 'Allow running with Xdebug for debugging purposes'),
				new InputOption('port', mode: InputOption::VALUE_REQUIRED),
				new InputOption('identifier', mode: InputOption::VALUE_REQUIRED),
				new InputOption('arena', mode: InputOption::VALUE_REQUIRED),
				new InputOption('tmp-file', mode: InputOption::VALUE_REQUIRED),
				new InputOption('instead-of', mode: InputOption::VALUE_REQUIRED),
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
		$port = $input->getOption('port');
		$identifier = $input->getOption('identifier');
		$arena = $input->getOption('arena');
		$tmpFile = $input->getOption('tmp-file');
		$insteadOfFile = $input->getOption('instead-of');

		if (
			!is_array($paths)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
			|| (!is_bool($allowXdebug))
			|| !is_string($port)
			|| !is_string($identifier)
			|| (!is_string($arena) && $arena !== null)
			|| (!is_string($tmpFile) && $tmpFile !== null)
			|| (!is_string($insteadOfFile) && $insteadOfFile !== null)
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
				$tmpFile,
				$insteadOfFile,
				false,
				deferBootstrapFiles: true,
			);
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		$container = $inceptionResult->getContainer();

		// Attach to the run's shared-memory arena (turbo extension only; the
		// seam is a no-op otherwise) before WorkerRunner sends its hello — the
		// master unlinks the arena name once every worker has checked in. A
		// failed attach (late respawn, extension absent) is fine: this worker
		// just computes everything locally. Forked workers skip this entirely —
		// they inherit the parent's mapping.
		if ($arena !== null) {
			ArenaCache::attach($arena);
		}

		// The master published the analysed-file list its job schedule was
		// built from; walking the analysed paths again would just re-derive
		// the same list. The parser router must still learn it — that is a
		// side effect of the walk this shortcut skips. (The router normally
		// also sees the statically configured stub files the excluder later
		// removes; those are never analysed, so their ignore-collection
		// routing is unused either way.)
		$analysedFiles = ArenaCache::lookup('analysed-files');
		if (is_array($analysedFiles)) {
			/** @var PathRoutingParser $pathRoutingParser */
			$pathRoutingParser = $container->getService('pathRoutingParser');
			$pathRoutingParser->setAnalysedFiles($analysedFiles);
		} else {
			try {
				[$analysedFiles] = $inceptionResult->getFiles();
			} catch (PathNotFoundException $e) {
				$inceptionResult->getErrorOutput()->writeLineFormatted(sprintf('<error>%s</error>', $e->getMessage()));
				return 1;
			} catch (InceptionNotSuccessfulException) {
				return 1;
			}
		}

		// Everything after the boot lives in WorkerRunner so a pcntl_fork()-ed
		// child can reuse it without re-booting (see ParallelAnalyser).
		$workerRunner = $container->getByType(WorkerRunner::class);

		try {
			return $workerRunner->run(
				$output,
				$analysedFiles,
				(int) $port,
				$identifier,
				$tmpFile,
				$insteadOfFile,
			);
		} catch (InceptionNotSuccessfulException) {
			// a deferred bootstrap file failed - its error is already printed
			return 1;
		}
	}

}
