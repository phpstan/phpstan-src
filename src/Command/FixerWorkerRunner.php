<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Clue\React\NDJson\Encoder;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\Ignore\IgnoredErrorHelperResult;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\ResultCache\LazyCollectedData;
use PHPStan\Analyser\ResultCache\ResultCacheManager;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parallel\ParallelAnalyser;
use PHPStan\Parallel\Scheduler;
use PHPStan\Process\CpuCoreCounter;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\TcpConnector;
use Symfony\Component\Console\Input\InputInterface;
use Throwable;
use function array_diff;
use function array_key_exists;
use function count;
use function filemtime;
use function filesize;
use function implode;
use function in_array;
use function is_file;
use function memory_get_peak_usage;
use function React\Promise\resolve;
use function sprintf;
use function usort;
use const JSON_INVALID_UTF8_IGNORE;

/**
 * The PHPStan Pro worker logic that runs *after* the application boot.
 *
 * FixerWorkerCommand calls it after the CommandHelper::begin() boot of the
 * process FixerApplication spawns for each analysis. The analysis itself
 * forks its workers where that is supported (see ParallelAnalyser).
 *
 * It connects back to FixerApplication's TCP server, restores the result
 * cache, analyses the changed files and streams the results.
 */
#[AutowiredService]
final class FixerWorkerRunner
{

	/**
	 * @param string[] $allConfigFiles
	 */
	public function __construct(
		#[AutowiredParameter]
		private array $allConfigFiles,
		#[AutowiredParameter]
		private string $usedLevel,
		private IgnoredErrorHelper $ignoredErrorHelper,
		private ResultCacheManagerFactory $resultCacheManagerFactory,
		private AnalyserResultFinalizer $analyserResultFinalizer,
		private ParallelAnalyser $parallelAnalyser,
		private Scheduler $scheduler,
		private CpuCoreCounter $cpuCoreCounter,
	)
	{
	}

	/**
	 * @param string[] $inceptionFiles
	 * @param mixed[]|null $projectConfigArray
	 */
	public function run(
		Output $errorOutput,
		array $inceptionFiles,
		bool $isOnlyFiles,
		?array $projectConfigArray,
		?string $configuration,
		int $serverPort,
		InputInterface $input,
	): int
	{
		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();

		$loop = new StreamSelectLoop();
		$tcpConnector = new TcpConnector($loop);
		$out = null;
		$tcpConnector->connect(sprintf('127.0.0.1:%d', $serverPort))->then(function (ConnectionInterface $connection) use ($errorOutput, $inceptionFiles, $isOnlyFiles, $projectConfigArray, $configuration, $input, $ignoredErrorHelperResult, $loop, &$out): PromiseInterface {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$out = new Encoder($connection, $jsonInvalidUtf8Ignore);

			// the config was edited since PHPStan Pro started, it reports what is wrong like the analyse command
			if (count($ignoredErrorHelperResult->getErrors()) > 0) {
				$out->write(['action' => 'analysisCrash', 'data' => [
					'internalErrors' => [new InternalError(
						implode("\n", $ignoredErrorHelperResult->getErrors()),
						'reading ignoreErrors in the configuration',
						trace: [],
						traceAsString: null,
						shouldReportBug: false,
					)],
				]]);
				$out->end([
					'action' => 'analysisEnd',
					'result' => [
						'ignoreFileErrors' => [],
						'ignoreNotFileErrors' => [],
					],
				]);

				return resolve(null);
			}
			//$in = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore, 128 * 1024 * 1024);

			/** @var ResultCacheManager $resultCacheManager */
			$resultCacheManager = $this->resultCacheManagerFactory->create([]);

			// the config read for this analysis, files included since PHPStan Pro started are watched from now on
			$out->write([
				'action' => 'watchedConfigFiles',
				'result' => [
					'files' => $this->allConfigFiles,
				],
			]);

			$out->write([
				'action' => 'analysisStart',
				'result' => [
					'analysedFiles' => $inceptionFiles,
					// the level of the config read for this analysis, it may have changed since PHPStan Pro started
					'ruleLevel' => $this->usedLevel,
				],
			]);

			$resultCache = $resultCacheManager->restore($inceptionFiles, false, false, $projectConfigArray, $errorOutput);

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

			$filesToAnalyse = $resultCache->getFilesToAnalyse();
			usort($filesToAnalyse, static function (string $a, string $b): int {
				$aTime = @filemtime($a);
				if ($aTime === false) {
					return 1;
				}

				$bTime = @filemtime($b);
				if ($bTime === false) {
					return -1;
				}

				// files are sorted from the oldest
				// because ParallelAnalyser reverses the scheduler jobs to do the smallest
				// jobs first
				return $aTime <=> $bTime;
			});

			return $this->runAnalyser(
				$loop,
				$filesToAnalyse,
				$inceptionFiles,
				$configuration,
				$input,
				function (array $errors, array $locallyIgnoredErrors, array $analysedFiles) use ($out, $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles): void {
					$internalErrors = [];
					foreach ($errors as $fileSpecificError) {
						if (!$this->isInternalError($fileSpecificError)) {
							continue;
						}

						$internalErrors[] = $this->transformErrorIntoInternalError($fileSpecificError);
					}

					if (count($internalErrors) > 0) {
						$out->write(['action' => 'analysisCrash', 'data' => [
							'internalErrors' => $internalErrors,
						]]);
						return;
					}

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
			)->then(function (AnalyserResult $intermediateAnalyserResult) use ($resultCacheManager, $resultCache, $errorOutput, $isOnlyFiles, $ignoredErrorHelperResult, $inceptionFiles, $out): void {
				$resultCacheResult = $resultCacheManager->process(
					$intermediateAnalyserResult,
					$resultCache,
					$errorOutput,
					false,
					true,
				);
				$finalizerResult = $this->analyserResultFinalizer->finalize($resultCacheResult->getAnalyserResult(), $isOnlyFiles, false, null, null, $resultCache->getCollectedDataRuleResults(), $resultCache->getAppearedSymbols());
				$resultCacheResult->save($finalizerResult);

				// FixerApplication's file monitor misses a file changed and changed back between two polls
				if ($resultCacheManager->getFilesChangedDuringAnalysis($resultCache) !== []) {
					$out->write(['action' => 'filesChangedWhileAnalysed', 'result' => []]);
				}

				$internalErrors = [];
				foreach ($finalizerResult->getAnalyserResult()->getInternalErrors() as $internalError) {
					$internalErrors[] = new InternalError(
						$internalError->getTraceAsString() !== null ? sprintf('Internal error: %s', $internalError->getMessage()) : $internalError->getMessage(),
						$internalError->getContextDescription(),
						$internalError->getTrace(),
						$internalError->getTraceAsString(),
						$internalError->shouldReportBug(),
					);
				}

				$hasParseErrors = false;
				foreach ($finalizerResult->getAnalyserResult()->getUnorderedErrors() as $fileSpecificError) {
					if (!$fileSpecificError->hasNonIgnorableException()) {
						continue;
					}
					if (!$this->isInternalError($fileSpecificError)) {
						$hasParseErrors = true;
						continue;
					}

					$internalErrors[] = $this->transformErrorIntoInternalError($fileSpecificError);
				}

				$hasInternalErrors = count($internalErrors) > 0 || $finalizerResult->getAnalyserResult()->hasReachedInternalErrorsCountLimit();
				// what is in a file that cannot be parsed is not known, so the ignores that did not match
				// anything there are not reported, like when the analysis is incomplete for other reasons
				$isAnalysisIncomplete = $hasInternalErrors || $hasParseErrors;

				if ($hasInternalErrors) {
					$out->write(['action' => 'analysisCrash', 'data' => [
						'internalErrors' => count($internalErrors) > 0 ? $internalErrors : [
							new InternalError(
								'Internal error occurred',
								'running analyser in PHPStan Pro worker',
								trace: [],
								traceAsString: null,
								shouldReportBug: false,
							),
						],
					]]);
				}

				[$collectorErrors, $ignoredCollectorErrors] = $this->filterErrors($finalizerResult->getCollectorErrors(), $ignoredErrorHelperResult, $isOnlyFiles, $inceptionFiles, $isAnalysisIncomplete);
				foreach ($finalizerResult->getLocallyIgnoredCollectorErrors() as $locallyIgnoredCollectorError) {
					$ignoredCollectorErrors[] = [$locallyIgnoredCollectorError, null];
				}
				$out->write([
					'action' => 'analysisStream',
					'result' => [
						'errors' => $collectorErrors,
						'ignoredErrors' => $ignoredCollectorErrors,
						'analysedFiles' => [],
					],
				]);

				$ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process(
					$finalizerResult->getErrors(),
					$isOnlyFiles,
					$inceptionFiles,
					$isAnalysisIncomplete,
				);
				$ignoreFileErrors = [];
				foreach ($ignoredErrorHelperProcessedResult->getNotIgnoredErrors() as $error) {
					if ($error->getIdentifier() === null) {
						continue;
					}
					if (!in_array($error->getIdentifier(), ['ignore.count', 'ignore.unmatched', 'ignore.unmatchedLine', 'ignore.unmatchedIdentifier', 'ignore.noComment'], true)) {
						continue;
					}
					$ignoreFileErrors[] = $error;
				}

				$out->end([
					'action' => 'analysisEnd',
					'result' => [
						'ignoreFileErrors' => $ignoreFileErrors,
						'ignoreNotFileErrors' => $ignoredErrorHelperProcessedResult->getOtherIgnoreMessages(),
					],
				]);
			});
		})->catch(static function (Throwable $e) use (&$out, $loop): void {
			// without an end the worker would wait forever and FixerApplication would not analyse again
			if (!$out instanceof Encoder) {
				// FixerApplication is gone
				$loop->stop();
				return;
			}

			$out->write(['action' => 'analysisCrash', 'data' => [
				'internalErrors' => [new InternalError(
					$e->getMessage(),
					'running PHPStan Pro worker',
					InternalError::prepareTrace($e),
					$e->getTraceAsString(),
					shouldReportBug: false,
				)],
			]]);
			$out->end([
				'action' => 'analysisEnd',
				'result' => [
					'ignoreFileErrors' => [],
					'ignoreNotFileErrors' => [],
				],
			]);
		});
		$loop->run();

		return 0;
	}

	/**
	 * An error with an exception that cannot be ignored, other than a parse error. A parse error
	 * describes the analysed code, so it is shown with the other errors, on its line.
	 */
	private function isInternalError(Error $error): bool
	{
		return $error->hasNonIgnorableException() && $error->getIdentifier() !== 'phpstan.parse';
	}

	private function transformErrorIntoInternalError(Error $error): InternalError
	{
		$message = $error->getMessage();
		$metadata = $error->getMetadata();
		if (
			$error->getIdentifier() === 'phpstan.internal'
			&& array_key_exists(InternalError::STACK_TRACE_AS_STRING_METADATA_KEY, $metadata)
		) {
			$message = sprintf('Internal error: %s', $message);
		}

		return new InternalError(
			$message,
			sprintf('analysing file %s', $error->getTraitFilePath() ?? $error->getFilePath()),
			$metadata[InternalError::STACK_TRACE_METADATA_KEY] ?? [],
			$metadata[InternalError::STACK_TRACE_AS_STRING_METADATA_KEY] ?? null,
			shouldReportBug: true,
		);
	}

	/**
	 * @param string[] $inceptionFiles
	 * @param list<Error> $errors
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
	 * @param string[] $files
	 * @param string[] $allAnalysedFiles
	 * @param callable(list<Error>, list<Error>, string[]): void $onFileAnalysisHandler
	 * @return PromiseInterface<AnalyserResult>
	 */
	private function runAnalyser(LoopInterface $loop, array $files, array $allAnalysedFiles, ?string $configuration, InputInterface $input, callable $onFileAnalysisHandler): PromiseInterface
	{
		$filesCount = count($files);
		if ($filesCount === 0) {
			return resolve(new AnalyserResult(
				unorderedErrors: [],
				filteredPhpErrors: [],
				allPhpErrors: [],
				locallyIgnoredErrors: [],
				linesToIgnore: [],
				unmatchedLineIgnores: [],
				internalErrors: [],
				collectedData: LazyCollectedData::fromArray([]),
				dependencies: [],
				usedTraitDependencies: [],
				valueDependencies: ['values' => [], 'dependents' => []],
				packageDependencies: [],
				exportedNodes: [],
				reachedInternalErrorsCountLimit: false,
				peakMemoryUsageBytes: memory_get_peak_usage(true),
				processedFiles: [],
			));
		}

		$schedule = $this->scheduler->scheduleWork($this->cpuCoreCounter->getNumberOfCpuCores(), $files, static fn (string $file): int => (int) @filesize($file));
		$mainScript = null;
		if (isset($_SERVER['argv'][0]) && is_file($_SERVER['argv'][0])) {
			$mainScript = $_SERVER['argv'][0];
		}

		return $this->parallelAnalyser->analyse(
			$loop,
			$schedule,
			$allAnalysedFiles,
			$mainScript,
			null,
			$configuration,
			null,
			null,
			$input,
			$onFileAnalysisHandler,
			null,
		);
	}

}
