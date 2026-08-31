<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Nette\Utils\Json;
use Override;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\File\PathNotFoundException;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function count;
use function date;
use function is_array;
use function is_bool;
use function is_string;
use function sprintf;

/**
 * Reports how the `analyse` command would use the result cache if it ran right now,
 * without analysing anything. Useful for deciding whether a result cache built
 * elsewhere (typically in CI) is worth downloading.
 */
final class ResultCacheInfoCommand extends Command
{

	private const NAME = 'result-cache-info';

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
			->setDescription('Shows how the result cache would be used by the next analysis')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('debug', mode: InputOption::VALUE_NONE, description: 'Show debug information - do not catch internal errors'),
				new InputOption('memory-limit', mode: InputOption::VALUE_REQUIRED, description: 'Memory limit for reading the result cache'),
				new InputOption('xdebug', mode: InputOption::VALUE_NONE, description: 'Allow running with Xdebug for debugging purposes'),
				new InputOption('json', mode: InputOption::VALUE_NONE, description: 'Print the information as JSON instead of a human-readable summary'),
				new InputOption('fail-without-result-cache', mode: InputOption::VALUE_NONE, description: 'Return non-zero exit code when the result cache would not be used'),
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
		$paths = $input->getArgument('paths');
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(AnalyseCommand::OPTION_LEVEL);
		$allowXdebug = $input->getOption('xdebug');
		$debugEnabled = (bool) $input->getOption('debug');
		$json = (bool) $input->getOption('json');
		$failWithoutResultCache = (bool) $input->getOption('fail-without-result-cache');

		if (
			!is_array($paths)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
			|| (!is_bool($allowXdebug))
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
				$debugEnabled,
				null,
				null,
				false,
				// the same as in the analyse command - the result cache is restored
				// before the bootstrap files run, so they must not run here either
				deferBootstrapFiles: true,
			);
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		$errorOutput = $inceptionResult->getErrorOutput();

		try {
			[$files, $onlyFiles] = $inceptionResult->getFiles();
		} catch (PathNotFoundException $e) {
			$errorOutput->writeLineFormatted(sprintf('<error>%s</error>', $e->getMessage()));
			return 1;
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		if (count($files) === 0) {
			$errorOutput->getStyle()->error('No files found to analyse.');

			return 1;
		}

		$container = $inceptionResult->getContainer();
		$resultCacheManager = $container->getByType(ResultCacheManagerFactory::class)->create([]);
		$resultCacheExists = $resultCacheManager->resultCacheExists();

		// debug mode is deliberately not passed along - it would always disable the
		// result cache and the command would have nothing to report
		$resultCache = $resultCacheManager->restore($files, false, $onlyFiles, $inceptionResult->getProjectConfigArray(), $errorOutput);

		$resultCacheUsed = !$resultCache->isFullAnalysis();
		$filesToAnalyseCount = count($resultCache->getFilesToAnalyse());
		$analysedFilesCount = count($files);
		$resultCachePath = $container->getParameter('resultCachePath');
		if (!is_string($resultCachePath)) {
			throw new ShouldNotHappenException();
		}

		$stdOutput = $inceptionResult->getStdOutput();
		if ($json) {
			$output->writeln(Json::encode([
				'resultCachePath' => $resultCachePath,
				'resultCacheExists' => $resultCacheExists,
				'resultCacheUsed' => $resultCacheUsed,
				'notUsedReason' => $resultCache->getFullAnalysisReason(),
				'analysedFilesCount' => $analysedFilesCount,
				'filesToAnalyseCount' => $filesToAnalyseCount,
				'lastFullAnalysisTime' => $resultCacheUsed ? $resultCache->getLastFullAnalysisTime() : null,
			], Json::PRETTY), OutputInterface::OUTPUT_RAW);
		} else {
			$stdOutput->writeLineFormatted(sprintf('Result cache file: <fg=cyan>%s</>', $resultCachePath));
			if ($resultCacheUsed) {
				$stdOutput->writeLineFormatted('Result cache will be used.');
				$stdOutput->writeLineFormatted(sprintf(
					'Last full analysis: <fg=cyan>%s</>',
					date('Y-m-d H:i:s', $resultCache->getLastFullAnalysisTime()),
				));
			} else {
				$stdOutput->writeLineFormatted('Result cache will not be used.');
				$reason = $resultCache->getFullAnalysisReason();
				if ($reason !== null) {
					$stdOutput->writeLineFormatted(sprintf('Reason: %s', $reason));
				}
			}
			$stdOutput->writeLineFormatted(sprintf(
				'%d out of %d %s will be analysed.',
				$filesToAnalyseCount,
				$analysedFilesCount,
				$analysedFilesCount === 1 ? 'file' : 'files',
			));
		}

		if ($failWithoutResultCache && !$resultCacheUsed) {
			return 2;
		}

		return 0;
	}

}
