<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Nette\Utils\Json;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheManager;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function count;
use function is_array;
use function is_bool;
use function is_string;

class FixerWorkerCommand extends Command
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

	protected function configure(): void
	{
		$this->setName(self::NAME)
			->setDescription('(Internal) Support for PHPStan Pro.')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for analysis'),
				new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with XDebug for debugging purposes'),
				new InputOption('tmp-file', null, InputOption::VALUE_REQUIRED),
				new InputOption('instead-of', null, InputOption::VALUE_REQUIRED),
				new InputOption('save-result-cache', null, InputOption::VALUE_OPTIONAL, '', false),
				new InputOption('restore-result-cache', null, InputOption::VALUE_REQUIRED),
				new InputOption('allow-parallel', null, InputOption::VALUE_NONE, 'Allow parallel analysis'),
			]);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$paths = $input->getArgument('paths');
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(AnalyseCommand::OPTION_LEVEL);
		$allowXdebug = $input->getOption('xdebug');
		$allowParallel = $input->getOption('allow-parallel');

		if (
			!is_array($paths)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
			|| (!is_bool($allowXdebug))
			|| (!is_bool($allowParallel))
		) {
			throw new ShouldNotHappenException();
		}

		/** @var string|null $tmpFile */
		$tmpFile = $input->getOption('tmp-file');

		/** @var string|null $insteadOfFile */
		$insteadOfFile = $input->getOption('instead-of');

		/** @var false|string|null $saveResultCache */
		$saveResultCache = $input->getOption('save-result-cache');

		/** @var string|null $restoreResultCache */
		$restoreResultCache = $input->getOption('restore-result-cache');
		if (is_string($tmpFile)) {
			if (!is_string($insteadOfFile)) {
				throw new ShouldNotHappenException();
			}
		} elseif (is_string($insteadOfFile)) {
			throw new ShouldNotHappenException();
		} elseif ($saveResultCache === false) {
			throw new ShouldNotHappenException();
		}

		$singleReflectionFile = null;
		if ($tmpFile !== null) {
			$singleReflectionFile = $tmpFile;
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
				$singleReflectionFile,
				$insteadOfFile,
				false,
			);
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		$container = $inceptionResult->getContainer();

		/** @var IgnoredErrorHelper $ignoredErrorHelper */
		$ignoredErrorHelper = $container->getByType(IgnoredErrorHelper::class);
		$ignoredErrorHelperResult = $ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			throw new ShouldNotHappenException();
		}

		/** @var AnalyserRunner $analyserRunner */
		$analyserRunner = $container->getByType(AnalyserRunner::class);

		$fileReplacements = [];
		if ($insteadOfFile !== null && $tmpFile !== null) {
			$fileReplacements = [$insteadOfFile => $tmpFile];
		}
		/** @var ResultCacheManager $resultCacheManager */
		$resultCacheManager = $container->getByType(ResultCacheManagerFactory::class)->create($fileReplacements);
		$projectConfigArray = $inceptionResult->getProjectConfigArray();
		[$inceptionFiles, $isOnlyFiles] = $inceptionResult->getFiles();
		$resultCache = $resultCacheManager->restore($inceptionFiles, false, false, $projectConfigArray, $inceptionResult->getErrorOutput(), $restoreResultCache);

		$intermediateAnalyserResult = $analyserRunner->runAnalyser(
			$resultCache->getFilesToAnalyse(),
			$inceptionFiles,
			null,
			null,
			false,
			$allowParallel,
			$configuration,
			$tmpFile,
			$insteadOfFile,
			$input,
		);
		$result = $resultCacheManager->process(
			$this->switchTmpFileInAnalyserResult($intermediateAnalyserResult, $tmpFile, $insteadOfFile),
			$resultCache,
			$inceptionResult->getErrorOutput(),
			false,
			is_string($saveResultCache) ? $saveResultCache : $saveResultCache === null,
		)->getAnalyserResult();

		$intermediateErrors = $ignoredErrorHelperResult->process(
			$result->getErrors(),
			$isOnlyFiles,
			$inceptionFiles,
			count($result->getInternalErrors()) > 0 || $result->hasReachedInternalErrorsCountLimit(),
		);
		$finalFileSpecificErrors = [];
		$finalNotFileSpecificErrors = [];
		foreach ($intermediateErrors as $intermediateError) {
			if (is_string($intermediateError)) {
				$finalNotFileSpecificErrors[] = $intermediateError;
				continue;
			}

			$finalFileSpecificErrors[] = $intermediateError;
		}

		$output->writeln(Json::encode([
			'fileSpecificErrors' => $finalFileSpecificErrors,
			'notFileSpecificErrors' => $finalNotFileSpecificErrors,
		]), OutputInterface::OUTPUT_RAW);

		return 0;
	}

	private function switchTmpFileInAnalyserResult(
		AnalyserResult $analyserResult,
		?string $insteadOfFile,
		?string $tmpFile,
	): AnalyserResult
	{
		$fileSpecificErrors = [];
		foreach ($analyserResult->getErrors() as $error) {
			if (
				$tmpFile !== null
				&& $insteadOfFile !== null
			) {
				if ($error->getFilePath() === $insteadOfFile) {
					$error = $error->changeFilePath($tmpFile);
				}
				if ($error->getTraitFilePath() === $insteadOfFile) {
					$error = $error->changeTraitFilePath($tmpFile);
				}
			}

			$fileSpecificErrors[] = $error;
		}

		$collectedData = [];
		foreach ($analyserResult->getCollectedData() as $data) {
			if (
				$tmpFile !== null
				&& $insteadOfFile !== null
			) {
				if ($data->getFilePath() === $insteadOfFile) {
					$data = $data->changeFilePath($tmpFile);
				}
			}

			$collectedData[] = $data;
		}

		$dependencies = null;
		if ($analyserResult->getDependencies() !== null) {
			$dependencies = [];
			foreach ($analyserResult->getDependencies() as $dependencyFile => $dependentFiles) {
				$new = [];
				foreach ($dependentFiles as $file) {
					if ($file === $insteadOfFile && $tmpFile !== null) {
						$new[] = $tmpFile;
						continue;
					}

					$new[] = $file;
				}

				$key = $dependencyFile;
				if ($key === $insteadOfFile && $tmpFile !== null) {
					$key = $tmpFile;
				}

				$dependencies[$key] = $new;
			}
		}

		$exportedNodes = [];
		foreach ($analyserResult->getExportedNodes() as $file => $fileExportedNodes) {
			if (
				$tmpFile !== null
				&& $insteadOfFile !== null
				&& $file === $insteadOfFile
			) {
				$file = $tmpFile;
			}

			$exportedNodes[$file] = $fileExportedNodes;
		}

		return new AnalyserResult(
			$fileSpecificErrors,
			$analyserResult->getInternalErrors(),
			$collectedData,
			$dependencies,
			$exportedNodes,
			$analyserResult->hasReachedInternalErrorsCountLimit(),
		);
	}

}
