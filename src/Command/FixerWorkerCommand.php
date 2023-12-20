<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Clue\React\NDJson\Encoder;
use PHPStan\AnalysedCodeException;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\Ignore\IgnoredErrorHelperResult;
use PHPStan\Analyser\ResultCache\ResultCacheManager;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Collectors\CollectedData;
use PHPStan\DependencyInjection\Container;
use PHPStan\File\PathNotFoundException;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Parallel\ParallelAnalyser;
use PHPStan\Parallel\Scheduler;
use PHPStan\Process\CpuCoreCounter;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\ShouldNotHappenException;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\TcpConnector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function array_diff;
use function count;
use function in_array;
use function is_array;
use function is_bool;
use function is_file;
use function is_string;
use function memory_get_peak_usage;
use function React\Promise\resolve;
use function sprintf;
use const JSON_INVALID_UTF8_IGNORE;

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
				new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with Xdebug for debugging purposes'),
				new InputOption('server-port', null, InputOption::VALUE_REQUIRED, 'Server port for FixerApplication'),
			])
			->setHidden(true);
	}

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

		$loop = new StreamSelectLoop();
		$tcpConnector = new TcpConnector($loop);
		$tcpConnector->connect(sprintf('127.0.0.1:%d', $serverPort))->done(function (ConnectionInterface $connection) use ($container, $inceptionResult, $configuration, $input, $ignoredErrorHelperResult, $loop): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$out = new Encoder($connection, $jsonInvalidUtf8Ignore);
			//$in = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore, 128 * 1024 * 1024);

			/** @var ResultCacheManager $resultCacheManager */
			$resultCacheManager = $container->getByType(ResultCacheManagerFactory::class)->create();
			$projectConfigArray = $inceptionResult->getProjectConfigArray();

			try {
				[$inceptionFiles, $isOnlyFiles] = $inceptionResult->getFiles();
			} catch (PathNotFoundException | InceptionNotSuccessfulException) {
				throw new ShouldNotHappenException();
			}

			$out->write([
				'action' => 'analysisStart',
				'result' => [
					'analysedFiles' => $inceptionFiles,
				],
			]);

			$resultCache = $resultCacheManager->restore($inceptionFiles, false, false, $projectConfigArray, $inceptionResult->getErrorOutput());

			$errorsFromResultCacheTmp = $resultCache->getErrors();
			$locallyIgnoredErrorsFromResultCacheTmp = $resultCache->getLocallyIgnoredErrors();
			foreach ($resultCache->getFilesToAnalyse() as $fileToAnalyse) {
				unset($errorsFromResultCacheTmp[$fileToAnalyse]);
				unset($locallyIgnoredErrorsFromResultCacheTmp[$fileToAnalyse]);
			}

			$errorsFromResultCache = [];
			foreach ($errorsFromResultCacheTmp as $errorsByFile) {
				foreach ($errorsByFile as $error) {
					$errorsFromResultCache[] = $error;
				}
			}

			[$errorsFromResultCache, $ignoredErrorsFromResultCache] = $this->filterErrors($errorsFromResultCache, $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles, false);

			foreach ($locallyIgnoredErrorsFromResultCacheTmp as $locallyIgnoredErrors) {
				foreach ($locallyIgnoredErrors as $locallyIgnoredError) {
					$ignoredErrorsFromResultCache[] = [$locallyIgnoredError, null];
				}
			}

			$out->write([
				'action' => 'analysisStream',
				'result' => [
					'errors' => $errorsFromResultCache,
					'ignoredErrors' => $ignoredErrorsFromResultCache,
					'analysedFiles' => array_diff($inceptionFiles, $resultCache->getFilesToAnalyse()),
				],
			]);

			$this->runAnalyser(
				$loop,
				$container,
				$resultCache->getFilesToAnalyse(),
				$configuration,
				$input,
				function (array $errors, array $locallyIgnoredErrors, array $analysedFiles) use ($out, $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles): void {
					[$errors, $ignoredErrors] = $this->filterErrors($errors, $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles, false);
					foreach ($locallyIgnoredErrors as $locallyIgnoredError) {
						$ignoredErrors[] = [$locallyIgnoredError, null];
					}
					$out->write([
						'action' => 'analysisStream',
						'result' => [
							'errors' => $errors,
							'ignoredErrors' => $ignoredErrors,
							'analysedFiles' => $analysedFiles,
						],
					]);
				},
			)->then(function (AnalyserResult $intermediateAnalyserResult) use ($resultCacheManager, $resultCache, $inceptionResult, $container, $isOnlyFiles, $ignoredErrorHelperResult, $inceptionFiles, $out): void {
				$result = $resultCacheManager->process(
					$intermediateAnalyserResult,
					$resultCache,
					$inceptionResult->getErrorOutput(),
					false,
					true,
				)->getAnalyserResult();

				$hasInternalErrors = count($result->getInternalErrors()) > 0 || $result->hasReachedInternalErrorsCountLimit();

				$collectorErrors = [];
				$intermediateErrors = $result->getErrors();
				if (!$hasInternalErrors) {
					foreach ($this->getCollectedDataErrors($container, $result->getCollectedData(), $isOnlyFiles) as $error) {
						$collectorErrors[] = $error;
						$intermediateErrors[] = $error;
					}
				} else {
					$out->write(['action' => 'analysisCrash', 'data' => [
						'errors' => count($result->getInternalErrors()) > 0 ? $result->getInternalErrors() : [
							'Internal error occurred',
						],
					]]);
				}

				[$collectorErrors, $ignoredCollectorErrors] = $this->filterErrors($collectorErrors, $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles, $hasInternalErrors);
				$out->write([
					'action' => 'analysisStream',
					'result' => [
						'errors' => $collectorErrors,
						'ignoredErrors' => $ignoredCollectorErrors,
						'analysedFiles' => [],
					],
				]);

				$ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process(
					$intermediateErrors,
					$isOnlyFiles,
					$inceptionFiles,
					$hasInternalErrors,
				);
				$intermediateErrors = $ignoredErrorHelperProcessedResult->getNotIgnoredErrors();
				$ignoreNotFileErrors = $ignoredErrorHelperProcessedResult->getOtherIgnoreMessages();

				$ignoreFileErrors = [];
				foreach ($intermediateErrors as $error) {
					if ($error->getIdentifier() === null) {
						continue;
					}
					if (!in_array($error->getIdentifier(), ['ignore.count', 'ignore.unmatched'], true)) {
						continue;
					}
					$ignoreFileErrors[] = $error;
				}

				$out->end([
					'action' => 'analysisEnd',
					'result' => [
						'ignoreFileErrors' => $ignoreFileErrors,
						'ignoreNotFileErrors' => $ignoreNotFileErrors,
					],
				]);
			});
		});
		$loop->run();

		return 0;
	}

	/**
	 * @param string[] $inceptionFiles
	 * @param array<Error> $errors
	 * @return array{list<Error>, list<array{Error, mixed[]|string}>}
	 */
	private function filterErrors(array $errors, IgnoredErrorHelperResult $ignoredErrorHelperResult, bool $onlyFiles, array $inceptionFiles, bool $hasInternalErrors): array
	{
		$ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process($errors, $onlyFiles, $inceptionFiles, $hasInternalErrors);
		$finalErrors = [];
		foreach ($ignoredErrorHelperProcessedResult->getNotIgnoredErrors() as $error) {
			if ($error->getIdentifier() === null) {
				$finalErrors[] = $error;
				continue;
			}
			if (in_array($error->getIdentifier(), ['ignore.count', 'ignore.unmatched'], true)) {
				continue;
			}
			$finalErrors[] = $error;
		}

		return [
			$finalErrors,
			$ignoredErrorHelperProcessedResult->getIgnoredErrors(),
		];
	}

	/**
	 * @param CollectedData[] $collectedData
	 * @return Error[]
	 */
	private function getCollectedDataErrors(Container $container, array $collectedData, bool $onlyFiles): array
	{
		$nodeType = CollectedDataNode::class;
		$node = new CollectedDataNode($collectedData, $onlyFiles);
		$file = 'N/A';
		$scope = $container->getByType(ScopeFactory::class)->create(ScopeContext::create($file));
		$ruleRegistry = $container->getByType(RuleRegistry::class);
		$ruleErrorTransformer = $container->getByType(RuleErrorTransformer::class);

		$errors = [];
		foreach ($ruleRegistry->getRules($nodeType) as $rule) {
			try {
				$ruleErrors = $rule->processNode($node, $scope);
			} catch (AnalysedCodeException $e) {
				$errors[] = (new Error($e->getMessage(), $file, $node->getStartLine(), $e, null, null, $e->getTip()))->withIdentifier('phpstan.internal');
				continue;
			} catch (IdentifierNotFound $e) {
				$errors[] = (new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, $node->getStartLine(), $e, null, null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection');
				continue;
			} catch (UnableToCompileNode | CircularReference $e) {
				$errors[] = (new Error(sprintf('Reflection error: %s', $e->getMessage()), $file, $node->getStartLine(), $e))->withIdentifier('phpstan.reflection');
				continue;
			}

			foreach ($ruleErrors as $ruleError) {
				$errors[] = $ruleErrorTransformer->transform($ruleError, $scope, $nodeType, $node->getStartLine());
			}
		}

		return $errors;
	}

	/**
	 * @param string[] $files
	 * @param callable(list<Error>, list<Error>, string[]): void $onFileAnalysisHandler
	 */
	private function runAnalyser(LoopInterface $loop, Container $container, array $files, ?string $configuration, InputInterface $input, callable $onFileAnalysisHandler): PromiseInterface
	{
		/** @var ParallelAnalyser $parallelAnalyser */
		$parallelAnalyser = $container->getByType(ParallelAnalyser::class);
		$filesCount = count($files);
		if ($filesCount === 0) {
			return resolve(new AnalyserResult([], [], [], [], [], [], false, memory_get_peak_usage(true)));
		}

		/** @var Scheduler $scheduler */
		$scheduler = $container->getByType(Scheduler::class);

		/** @var CpuCoreCounter $cpuCoreCounter */
		$cpuCoreCounter = $container->getByType(CpuCoreCounter::class);

		$schedule = $scheduler->scheduleWork($cpuCoreCounter->getNumberOfCpuCores(), $files);
		$mainScript = null;
		if (isset($_SERVER['argv'][0]) && is_file($_SERVER['argv'][0])) {
			$mainScript = $_SERVER['argv'][0];
		}

		return $parallelAnalyser->analyse(
			$loop,
			$schedule,
			$mainScript,
			null,
			$configuration,
			$input,
			$onFileAnalysisHandler,
		);
	}

}
