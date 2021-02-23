<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\Internal\BytesHelper;
use PHPStan\PhpDoc\StubValidator;
use Symfony\Component\Console\Input\InputInterface;

class AnalyseApplication
{

	private AnalyserRunner $analyserRunner;

	private \PHPStan\PhpDoc\StubValidator $stubValidator;

	private \PHPStan\Analyser\ResultCache\ResultCacheManagerFactory $resultCacheManagerFactory;

	private IgnoredErrorHelper $ignoredErrorHelper;

	private string $memoryLimitFile;

	private int $internalErrorsCountLimit;

	public function __construct(
		AnalyserRunner $analyserRunner,
		StubValidator $stubValidator,
		ResultCacheManagerFactory $resultCacheManagerFactory,
		IgnoredErrorHelper $ignoredErrorHelper,
		string $memoryLimitFile,
		int $internalErrorsCountLimit
	)
	{
		$this->analyserRunner = $analyserRunner;
		$this->stubValidator = $stubValidator;
		$this->resultCacheManagerFactory = $resultCacheManagerFactory;
		$this->ignoredErrorHelper = $ignoredErrorHelper;
		$this->memoryLimitFile = $memoryLimitFile;
		$this->internalErrorsCountLimit = $internalErrorsCountLimit;
	}

	/**
	 * @param string[] $files
	 * @param bool $onlyFiles
	 * @param \PHPStan\Command\Output $stdOutput
	 * @param \PHPStan\Command\Output $errorOutput
	 * @param bool $defaultLevelUsed
	 * @param bool $debug
	 * @param string|null $projectConfigFile
	 * @param mixed[]|null $projectConfigArray
	 * @return AnalysisResult
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
		InputInterface $input
	): AnalysisResult
	{
		$this->updateMemoryLimitFile();
		$projectStubFiles = [];
		if ($projectConfigArray !== null) {
			$projectStubFiles = $projectConfigArray['parameters']['stubFiles'] ?? [];
		}
		$stubErrors = $this->stubValidator->validate($projectStubFiles);

		register_shutdown_function(function (): void {
			$error = error_get_last();
			if ($error === null) {
				return;
			}
			if ($error['type'] !== E_ERROR) {
				return;
			}

			if (strpos($error['message'], 'Allowed memory size') !== false) {
				return;
			}

			@unlink($this->memoryLimitFile);
		});

		$resultCacheManager = $this->resultCacheManagerFactory->create([]);

		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			$errors = $ignoredErrorHelperResult->getErrors();
			$warnings = [];
			$internalErrors = [];
			$savedResultCache = false;
			if ($errorOutput->isDebug()) {
				$errorOutput->writeLineFormatted('Result cache was not saved because of ignoredErrorHelperResult errors.');
			}
		} else {
			$resultCache = $resultCacheManager->restore($files, $debug, $onlyFiles, $projectConfigArray, $errorOutput);
			$intermediateAnalyserResult = $this->runAnalyser(
				$resultCache->getFilesToAnalyse(),
				$files,
				$debug,
				$projectConfigFile,
				$stdOutput,
				$errorOutput,
				$input
			);
			$resultCacheResult = $resultCacheManager->process($intermediateAnalyserResult, $resultCache, $errorOutput, $onlyFiles, true);
			$analyserResult = $resultCacheResult->getAnalyserResult();
			$internalErrors = $analyserResult->getInternalErrors();
			$errors = $ignoredErrorHelperResult->process($analyserResult->getErrors(), $onlyFiles, $files, count($internalErrors) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit());
			$warnings = $ignoredErrorHelperResult->getWarnings();
			$savedResultCache = $resultCacheResult->isSaved();
			if ($analyserResult->hasReachedInternalErrorsCountLimit()) {
				$errors[] = sprintf('Reached internal errors count limit of %d, exiting...', $this->internalErrorsCountLimit);
			}
			$errors = array_merge($errors, $internalErrors);
		}

		$errors = array_merge($stubErrors, $errors);

		$fileSpecificErrors = [];
		$notFileSpecificErrors = [];
		foreach ($errors as $error) {
			if (is_string($error)) {
				$notFileSpecificErrors[] = $error;
				continue;
			}

			$fileSpecificErrors[] = $error;
		}

		return new AnalysisResult(
			$fileSpecificErrors,
			$notFileSpecificErrors,
			$internalErrors,
			$warnings,
			$defaultLevelUsed,
			$projectConfigFile,
			$savedResultCache
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
		InputInterface $input
	): AnalyserResult
	{
		$filesCount = count($files);
		$allAnalysedFilesCount = count($allAnalysedFiles);
		if ($filesCount === 0) {
			$errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
			$errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount);
			$errorOutput->getStyle()->progressFinish();
			return new AnalyserResult([], [], [], [], false);
		}

		if (!$debug) {
			$progressStarted = false;
			$fileOrder = 0;
			$preFileCallback = null;
			$postFileCallback = function (int $step, ?string $file = null) use ($errorOutput, &$progressStarted, $allAnalysedFilesCount, $filesCount, &$fileOrder): void {
				if (!$progressStarted) {
					$errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
					$errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount - $filesCount);
					$progressStarted = true;
				}
				$errorOutput->getStyle()->progressAdvance($step);

				if ($fileOrder >= 100) {
					$this->updateMemoryLimitFile();
					$fileOrder = 0;
				}
				$fileOrder += $step;
			};
		} else {
			$preFileCallback = static function (string $file) use ($stdOutput): void {
				$stdOutput->writeLineFormatted($file);
			};
			$postFileCallback = null;
			if ($stdOutput->isDebug()) {
				$times = [];
				$memories = [];
				$previousMemory = memory_get_peak_usage(true);
				$previousTimestamp = microtime(true);
				$i = 0;
				$postFileCallback = static function (int $step, ?string $file = null) use ($stdOutput, &$previousMemory, &$previousTimestamp, &$i, $allAnalysedFilesCount, &$times, &$memories): void {
					$i += $step;
					$currentTimestamp = microtime(true);
					$timeElapsedSecs = round($currentTimestamp - $previousTimestamp, 2);
					$currentTotalMemory = memory_get_peak_usage(true);
					$stdOutput->writeLineFormatted(sprintf('(%s/%s) %s: consumed %s, total %s, time %s s', $i, $allAnalysedFilesCount, $file, BytesHelper::bytes($currentTotalMemory - $previousMemory), BytesHelper::bytes($currentTotalMemory), $timeElapsedSecs));
					$times[$file] = $timeElapsedSecs;
					$memories[$file] = $currentTotalMemory - $previousMemory;
					$previousMemory = $currentTotalMemory;
					$previousTimestamp = $currentTimestamp;
				};
			}
		}

		$analyserResult = $this->analyserRunner->runAnalyser($files, $allAnalysedFiles, $preFileCallback, $postFileCallback, $debug, true, $projectConfigFile, null, null, $input);

		if (isset($progressStarted) && $progressStarted) {
			$errorOutput->getStyle()->progressFinish();
		}

		if (isset($times) && count($times) > 1) {
			echo "\n\n== Files with longest analyzis time ==\n\n";
			arsort($times, SORT_NUMERIC);
			foreach (array_slice($times, 0, 25, true) as $file => $time) {
				echo $file . ' -> ' . $time . " s\n";
			}
		}
		if (isset($memories) && count($memories) > 1) {
			echo "\n\n== Files with highest memory consumption ==\n\n";
			arsort($memories, SORT_NUMERIC);
			foreach (array_slice($memories, 0, 25, true) as $file => $memory) {
				echo $file . ' -> ' . BytesHelper::bytes($memory) . "\n";
			}
			echo "\n";
		}

		return $analyserResult;
	}

	private function updateMemoryLimitFile(): void
	{
		$bytes = memory_get_peak_usage(true);
		$megabytes = ceil($bytes / 1024 / 1024);
		file_put_contents($this->memoryLimitFile, sprintf('%d MB', $megabytes));
	}

}
