<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\Internal\BytesHelper;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\PhpDoc\StubValidator;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Input\InputInterface;
use function array_merge;
use function count;
use function is_file;
use function memory_get_peak_usage;
use function microtime;
use function sha1_file;
use function sprintf;

class AnalyseApplication
{

	public function __construct(
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
		InputInterface $input,
	): AnalysisResult
	{
		$isResultCacheUsed = false;
		$resultCacheManager = $this->resultCacheManagerFactory->create();

		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		$fileSpecificErrors = [];
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			$notFileSpecificErrors = $ignoredErrorHelperResult->getErrors();
			$internalErrors = [];
			$collectedData = [];
			$savedResultCache = false;
			$memoryUsageBytes = memory_get_peak_usage(true);
			if ($errorOutput->isDebug()) {
				$errorOutput->writeLineFormatted('Result cache was not saved because of ignoredErrorHelperResult errors.');
			}
			$changedProjectExtensionFilesOutsideOfAnalysedPaths = [];
		} else {
			$resultCache = $resultCacheManager->restore($files, $debug, $onlyFiles, $projectConfigArray, $errorOutput);
			$intermediateAnalyserResult = $this->runAnalyser(
				$resultCache->getFilesToAnalyse(),
				$files,
				$debug,
				$projectConfigFile,
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
					array_merge($intermediateAnalyserResult->getUnorderedErrors(), $stubErrors),
					$intermediateAnalyserResult->getFilteredPhpErrors(),
					$intermediateAnalyserResult->getAllPhpErrors(),
					$intermediateAnalyserResult->getLocallyIgnoredErrors(),
					$intermediateAnalyserResult->getLinesToIgnore(),
					$intermediateAnalyserResult->getUnmatchedLineIgnores(),
					$intermediateAnalyserResult->getInternalErrors(),
					$intermediateAnalyserResult->getCollectedData(),
					$intermediateAnalyserResult->getDependencies(),
					$intermediateAnalyserResult->getExportedNodes(),
					$intermediateAnalyserResult->hasReachedInternalErrorsCountLimit(),
					$intermediateAnalyserResult->getPeakMemoryUsageBytes(),
				);
			}

			$resultCacheResult = $resultCacheManager->process($intermediateAnalyserResult, $resultCache, $errorOutput, $onlyFiles, true);
			$analyserResult = $this->analyserResultFinalizer->finalize($resultCacheResult->getAnalyserResult(), $onlyFiles, $debug)->getAnalyserResult();
			$internalErrors = $analyserResult->getInternalErrors();
			$errors = array_merge(
				$analyserResult->getErrors(),
				$analyserResult->getFilteredPhpErrors(),
			);
			$hasInternalErrors = count($internalErrors) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
			$memoryUsageBytes = $analyserResult->getPeakMemoryUsageBytes();
			$isResultCacheUsed = !$resultCache->isFullAnalysis();

			$changedProjectExtensionFilesOutsideOfAnalysedPaths = [];
			if (
				$isResultCacheUsed
				&& $resultCacheResult->isSaved()
				&& !$onlyFiles
				&& $projectConfigArray !== null
			) {
				foreach ($resultCache->getProjectExtensionFiles() as $file => [$hash, $isAnalysed, $className]) {
					if ($isAnalysed) {
						continue;
					}

					if (!is_file($file)) {
						$changedProjectExtensionFilesOutsideOfAnalysedPaths[$file] = $className;
						continue;
					}

					$newHash = sha1_file($file);
					if ($newHash === $hash) {
						continue;
					}

					$changedProjectExtensionFilesOutsideOfAnalysedPaths[$file] = $className;
				}
			}

			$ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process($errors, $onlyFiles, $files, $hasInternalErrors);
			$fileSpecificErrors = $ignoredErrorHelperProcessedResult->getNotIgnoredErrors();
			$notFileSpecificErrors = $ignoredErrorHelperProcessedResult->getOtherIgnoreMessages();
			$collectedData = $analyserResult->getCollectedData();
			$savedResultCache = $resultCacheResult->isSaved();
		}

		return new AnalysisResult(
			$fileSpecificErrors,
			$notFileSpecificErrors,
			$internalErrors,
			[],
			$collectedData,
			$defaultLevelUsed,
			$projectConfigFile,
			$savedResultCache,
			$memoryUsageBytes,
			$isResultCacheUsed,
			$changedProjectExtensionFilesOutsideOfAnalysedPaths,
		);
	}

	/**
	 * @param string[] $files
	 * @param string[] $allAnalysedFiles
	 */
	private function runAnalyser(
		array $files,
		array $allAnalysedFiles,
		bool $debug,
		?string $projectConfigFile,
		Output $stdOutput,
		Output $errorOutput,
		InputInterface $input,
	): AnalyserResult
	{
		$filesCount = count($files);
		$allAnalysedFilesCount = count($allAnalysedFiles);
		if ($filesCount === 0) {
			$errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
			$errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount);
			$errorOutput->getStyle()->progressFinish();
			return new AnalyserResult([], [], [], [], [], [], [], [], [], [], false, memory_get_peak_usage(true));
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
				$postFileCallback = static function () use ($stdOutput, &$previousMemory, &$startTime): void {
					if ($startTime === null) {
						throw new ShouldNotHappenException();
					}
					$currentTotalMemory = memory_get_peak_usage(true);
					$elapsedTime = microtime(true) - $startTime;
					$stdOutput->writeLineFormatted(sprintf('--- consumed %s, total %s, took %.2f s', BytesHelper::bytes($currentTotalMemory - $previousMemory), BytesHelper::bytes($currentTotalMemory), $elapsedTime));
					$previousMemory = $currentTotalMemory;
				};
			}
		}

		$analyserResult = $this->analyserRunner->runAnalyser($files, $allAnalysedFiles, $preFileCallback, $postFileCallback, $debug, true, $projectConfigFile, $input);

		if (!$debug) {
			$errorOutput->getStyle()->progressFinish();
		}

		return $analyserResult;
	}

}
