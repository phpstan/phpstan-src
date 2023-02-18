<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Nette\Utils\Json;
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
				new InputOption('save-result-cache', null, InputOption::VALUE_OPTIONAL, '', false),
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

		/** @var false|string|null $saveResultCache */
		$saveResultCache = $input->getOption('save-result-cache');

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

		/** @var ResultCacheManager $resultCacheManager */
		$resultCacheManager = $container->getByType(ResultCacheManagerFactory::class)->create();
		$projectConfigArray = $inceptionResult->getProjectConfigArray();
		[$inceptionFiles, $isOnlyFiles] = $inceptionResult->getFiles();
		$resultCache = $resultCacheManager->restore($inceptionFiles, false, false, $projectConfigArray, $inceptionResult->getErrorOutput());

		$intermediateAnalyserResult = $analyserRunner->runAnalyser(
			$resultCache->getFilesToAnalyse(),
			$inceptionFiles,
			null,
			null,
			false,
			$allowParallel,
			$configuration,
			$input,
		);
		$result = $resultCacheManager->process(
			$intermediateAnalyserResult,
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

}
