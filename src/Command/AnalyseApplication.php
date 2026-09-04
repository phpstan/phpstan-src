<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyserResult;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\LazyCollectedData;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\Collectors\CollectedData;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\BytesHelper;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\PhpDoc\StubValidator;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Input\InputInterface;
use function array_merge;
use function array_unique;
use function count;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function memory_get_peak_usage;
use function microtime;
use function sprintf;

/**
 * @phpstan-import-type CollectorData from CollectedData
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 */
#[AutowiredService]
final class AnalyseApplication
{

	public function __construct(
		private BootstrapFilesRunner $bootstrapFilesRunner,
		private AnalyserRunner $analyserRunner,
		private AnalyserResultFinalizer $analyserResultFinalizer,
		private StubValidator $stubValidator,
		private ResultCacheManagerFactory $resultCacheManagerFactory,
		private IgnoredErrorHelper $ignoredErrorHelper,
		private StubFilesProvider $stubFilesProvider,
	)
	{
	}

	/**
	 * @param string[] $files
	 * @param mixed[]|null $projectConfigArray
	 * @throws InceptionNotSuccessfulException
	 */
	public function analyse(
		array $files,
		bool $onlyFiles,
		Output $stdOutput,
		Output $errorOutput,
		bool $defaultLevelUsed,
		bool $debug,
		?string $projectConfigFile,
		?array $projectConfigArray,
		?string $tmpFile,
		?string $insteadOfFile,
		InputInterface $input,
	): AnalysisResult
	{
		$isResultCacheUsed = false;
		$fileReplacements = [];
		if ($tmpFile !== null && $insteadOfFile !== null) {
			$fileReplacements = [$insteadOfFile => $tmpFile];
		}
		$resultCacheManager = $this->resultCacheManagerFactory->create($fileReplacements);
		$resultCacheExisted = $resultCacheManager->resultCacheExists();

		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		$fileSpecificErrors = [];
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			$notFileSpecificErrors = $ignoredErrorHelperResult->getErrors();
			$internalErrors = [];
			$collectedData = LazyCollectedData::fromArray([]);
			$savedResultCache = false;
			$memoryUsageBytes = 0;
			$workerCount = 0;
			$processedFiles = [];
			if ($errorOutput->isVeryVerbose()) {
				$errorOutput->writeLineFormatted('Result cache was not saved because of ignoredErrorHelperResult errors.');
			}
		} else {
			$resultCache = $resultCacheManager->restore($files, $debug, $onlyFiles, $projectConfigArray, $errorOutput);
			$intermediateAnalyserResult = $this->runAnalyser(
				$resultCache->getFilesToAnalyse(),
				$files,
				$debug,
				$projectConfigFile,
				$tmpFile,
				$insteadOfFile,
				$stdOutput,
				$errorOutput,
				$input,
			);

			$projectStubFiles = $this->stubFilesProvider->getProjectStubFiles();

			$forceValidateStubFiles = (bool) ($_SERVER['__PHPSTAN_FORCE_VALIDATE_STUB_FILES'] ?? false);
			if (
				$resultCache->isFullAnalysis()
				&& count($projectStubFiles) !== 0
				&& (!$onlyFiles || $forceValidateStubFiles)
			) {
				$stubErrors = $this->stubValidator->validate($projectStubFiles, $debug);
				$intermediateAnalyserResult = new AnalyserResult(
					unorderedErrors: array_merge($intermediateAnalyserResult->getUnorderedErrors(), $stubErrors),
					filteredPhpErrors: $intermediateAnalyserResult->getFilteredPhpErrors(),
					allPhpErrors: $intermediateAnalyserResult->getAllPhpErrors(),
					locallyIgnoredErrors: $intermediateAnalyserResult->getLocallyIgnoredErrors(),
					linesToIgnore: $intermediateAnalyserResult->getLinesToIgnore(),
					unmatchedLineIgnores: $intermediateAnalyserResult->getUnmatchedLineIgnores(),
					internalErrors: $intermediateAnalyserResult->getInternalErrors(),
					collectedData: $intermediateAnalyserResult->getLazyCollectedData(),
					dependencies: $intermediateAnalyserResult->getDependencies(),
					usedTraitDependencies: $intermediateAnalyserResult->getUsedTraitDependencies(),
					packageDependencies: $intermediateAnalyserResult->getPackageDependencies(),
					exportedNodes: $intermediateAnalyserResult->getExportedNodes(),
					reachedInternalErrorsCountLimit: $intermediateAnalyserResult->hasReachedInternalErrorsCountLimit(),
					peakMemoryUsageBytes: $intermediateAnalyserResult->getPeakMemoryUsageBytes(),
					processedFiles: $intermediateAnalyserResult->getProcessedFiles(),
					workerCount: $intermediateAnalyserResult->getWorkerCount(),
				);
			}

			$processedFiles = $intermediateAnalyserResult->getProcessedFiles();

			$resultCacheResult = $resultCacheManager->process($intermediateAnalyserResult, $resultCache, $errorOutput, $onlyFiles, true);
			$analyserResult = $this->analyserResultFinalizer->finalize(
				$this->switchTmpFileInAnalyserResult($resultCacheResult->getAnalyserResult(), $insteadOfFile, $tmpFile),
				$onlyFiles,
				$debug,
			)->getAnalyserResult();
			$internalErrors = $analyserResult->getInternalErrors();
			$errors = array_merge(
				$analyserResult->getErrors(),
				$analyserResult->getFilteredPhpErrors(),
			);
			$hasInternalErrors = count($internalErrors) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
			$memoryUsageBytes = $analyserResult->getPeakMemoryUsageBytes();
			$workerCount = $analyserResult->getWorkerCount();
			$isResultCacheUsed = !$resultCache->isFullAnalysis();

			$ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process($errors, $onlyFiles, $files, $hasInternalErrors);
			$fileSpecificErrors = $ignoredErrorHelperProcessedResult->getNotIgnoredErrors();
			$notFileSpecificErrors = $ignoredErrorHelperProcessedResult->getOtherIgnoreMessages();
			$collectedData = $analyserResult->getLazyCollectedData();
			$savedResultCache = $resultCacheResult->isSaved();
		}

		return new AnalysisResult(
			$fileSpecificErrors,
			$notFileSpecificErrors,
			$internalErrors,
			[],
			fn (): array => $this->mapCollectedData($collectedData->toArray()),
			$defaultLevelUsed,
			$projectConfigFile,
			$savedResultCache,
			$memoryUsageBytes,
			$isResultCacheUsed,
			$processedFiles,
			$resultCacheExisted,
			$workerCount,
		);
	}

	/**
	 * @param CollectorData $collectedData
	 *
	 * @return list<CollectedData>
	 */
	private function mapCollectedData(array $collectedData): array
	{
		$result = [];
		foreach ($collectedData as $file => $dataPerCollector) {
			foreach ($dataPerCollector as $collectorType => $rawData) {
				$result[] = new CollectedData($rawData, $file, $collectorType);
			}
		}
		return $result;
	}

	/**
	 * @param string[] $files
	 * @param string[] $allAnalysedFiles
	 * @throws InceptionNotSuccessfulException
	 */
	private function runAnalyser(
		array $files,
		array $allAnalysedFiles,
		bool $debug,
		?string $projectConfigFile,
		?string $tmpFile,
		?string $insteadOfFile,
		Output $stdOutput,
		Output $errorOutput,
		InputInterface $input,
	): AnalyserResult
	{
		$filesCount = count($files);
		$allAnalysedFilesCount = count($allAnalysedFiles);
		if ($filesCount === 0) {
			// nothing to analyse, but the deferred bootstrapFiles still must
			// run in the main thread: the phases that follow may reflect
			// analysed code (stub validation, collector rules from the result
			// cache)
			$this->bootstrapFilesRunner->run($errorOutput, $debug);
			$errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
			$errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount);
			$errorOutput->getStyle()->progressFinish();
			return new AnalyserResult(
				unorderedErrors: [],
				filteredPhpErrors: [],
				allPhpErrors: [],
				locallyIgnoredErrors: [],
				linesToIgnore: [],
				unmatchedLineIgnores: [],
				internalErrors: [],
				collectedData: [],
				dependencies: [],
				usedTraitDependencies: [],
				packageDependencies: [],
				exportedNodes: [],
				reachedInternalErrorsCountLimit: false,
				peakMemoryUsageBytes: 0,
				processedFiles: [],
			);
		}

		if (!$debug) {
			$preFileCallback = null;
			$postFileCallback = static function (int $step) use ($errorOutput): void {
				$errorOutput->getStyle()->progressAdvance($step);
			};

			$errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
			$errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount - $filesCount);
		} else {
			$startTime = null;
			$preFileCallback = static function (string $file) use ($stdOutput, &$startTime): void {
				$stdOutput->writeLineFormatted($file);
				$startTime = microtime(true);
			};
			$postFileCallback = null;
			if ($stdOutput->isDebug()) {
				$previousMemory = memory_get_peak_usage(true);
				$postFileCallback = static function (int $step, array $processedFiles = []) use ($stdOutput, &$previousMemory, &$startTime, &$linesOfCode): void {
					if ($startTime === null) {
						throw new ShouldNotHappenException();
					}
					$currentTotalMemory = memory_get_peak_usage(true);
					$elapsedTime = microtime(true) - $startTime;

					$linesOfCode = 0;
					foreach (array_unique($processedFiles) as $processedFile) {
						$handle = @fopen($processedFile, 'r');
						if ($handle === false) {
							continue;
						}

						while (!feof($handle)) {
							fgets($handle);
							$linesOfCode++;
						}
						fclose($handle);
					}

					$stdOutput->writeLineFormatted(sprintf('--- consumed %s, total %s, took %.2f s, %.3f LoC/s', BytesHelper::bytes($currentTotalMemory - $previousMemory), BytesHelper::bytes($currentTotalMemory), $elapsedTime, $linesOfCode / $elapsedTime));
					$previousMemory = $currentTotalMemory;
				};
			}
		}

		$analyserResult = $this->analyserRunner->runAnalyser($files, $allAnalysedFiles, $preFileCallback, $postFileCallback, $debug, true, $projectConfigFile, $tmpFile, $insteadOfFile, $input, $errorOutput);

		if (!$debug) {
			$errorOutput->getStyle()->progressFinish();
		}

		return $analyserResult;
	}

	private function switchTmpFileInAnalyserResult(
		AnalyserResult $analyserResult,
		?string $insteadOfFile,
		?string $tmpFile,
	): AnalyserResult
	{
		if ($insteadOfFile === null || $tmpFile === null) {
			return $analyserResult;
		}

		$dependencies = null;
		if ($analyserResult->getDependencies() !== null) {
			$dependencies = $this->switchTmpFileInDependencies($analyserResult->getDependencies(), $insteadOfFile, $tmpFile);
		}
		$usedTraitDependencies = null;
		if ($analyserResult->getUsedTraitDependencies() !== null) {
			$usedTraitDependencies = $this->switchTmpFileInDependencies($analyserResult->getUsedTraitDependencies(), $insteadOfFile, $tmpFile);
		}
		$packageDependencies = null;
		if ($analyserResult->getPackageDependencies() !== null) {
			$packageDependencies = $this->switchTmpFileInDependencies($analyserResult->getPackageDependencies(), $insteadOfFile, $tmpFile);
		}

		$exportedNodes = [];
		foreach ($analyserResult->getExportedNodes() as $file => $fileExportedNodes) {
			if ($file === $tmpFile) {
				$file = $insteadOfFile;
			}

			$exportedNodes[$file] = $fileExportedNodes;
		}

		return new AnalyserResult(
			unorderedErrors: $this->switchTmpFileInErrors($analyserResult->getUnorderedErrors(), $insteadOfFile, $tmpFile),
			filteredPhpErrors: $this->switchTmpFileInErrors($analyserResult->getFilteredPhpErrors(), $insteadOfFile, $tmpFile),
			allPhpErrors: $this->switchTmpFileInErrors($analyserResult->getAllPhpErrors(), $insteadOfFile, $tmpFile),
			locallyIgnoredErrors: $this->switchTmpFileInErrors($analyserResult->getLocallyIgnoredErrors(), $insteadOfFile, $tmpFile),
			linesToIgnore: $this->switchTmpFileInLinesToIgnore($analyserResult->getLinesToIgnore(), $insteadOfFile, $tmpFile),
			unmatchedLineIgnores: $this->switchTmpFileInLinesToIgnore($analyserResult->getUnmatchedLineIgnores(), $insteadOfFile, $tmpFile),
			internalErrors: $analyserResult->getInternalErrors(),
			collectedData: $analyserResult->getLazyCollectedData()->withRenamedFile($tmpFile, $insteadOfFile),
			dependencies: $dependencies,
			usedTraitDependencies: $usedTraitDependencies,
			packageDependencies: $packageDependencies,
			exportedNodes: $exportedNodes,
			reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
			peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
			processedFiles: $analyserResult->getProcessedFiles(),
		);
	}

	/**
	 * @param array<string, array<string>> $dependencies
	 * @return array<string, array<string>>
	 */
	private function switchTmpFileInDependencies(array $dependencies, string $insteadOfFile, string $tmpFile): array
	{
		$newDependencies = [];
		foreach ($dependencies as $dependencyFile => $dependentFiles) {
			$new = [];
			foreach ($dependentFiles as $file) {
				if ($file === $tmpFile) {
					$new[] = $insteadOfFile;
					continue;
				}

				$new[] = $file;
			}

			$key = $dependencyFile;
			if ($key === $tmpFile) {
				$key = $insteadOfFile;
			}

			$newDependencies[$key] = $new;
		}

		return $newDependencies;
	}

	/**
	 * @param list<Error> $errors
	 * @return list<Error>
	 */
	private function switchTmpFileInErrors(array $errors, string $insteadOfFile, string $tmpFile): array
	{
		$newErrors = [];
		foreach ($errors as $error) {
			if ($error->getFilePath() === $tmpFile) {
				$error = $error->changeFilePath($insteadOfFile);
			}
			if ($error->getTraitFilePath() === $tmpFile) {
				$error = $error->changeTraitFilePath($insteadOfFile);
			}

			$newErrors[] = $error;
		}

		return $newErrors;
	}

	/**
	 * @param array<string, LinesToIgnore> $linesToIgnore
	 * @return array<string, LinesToIgnore>
	 */
	private function switchTmpFileInLinesToIgnore(array $linesToIgnore, string $insteadOfFile, string $tmpFile): array
	{
		$newLinesToIgnore = [];
		foreach ($linesToIgnore as $file => $lines) {
			if ($file === $tmpFile) {
				$file = $insteadOfFile;
			}

			$newLines = [];
			foreach ($lines as $f => $line) {
				if ($f === $tmpFile) {
					$f = $insteadOfFile;
				}

				$newLines[$f] = $line;
			}

			$newLinesToIgnore[$file] = $newLines;
		}

		return $newLinesToIgnore;
	}

}
