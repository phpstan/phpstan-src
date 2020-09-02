<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheManager;
use PHPStan\PhpDoc\StubValidator;
use Symfony\Component\Console\Input\InputInterface;

class AnalyseApplication
{

	private AnalyserRunner $analyserRunner;

	private \PHPStan\PhpDoc\StubValidator $stubValidator;

	private \PHPStan\Analyser\ResultCache\ResultCacheManager $resultCacheManager;

	private IgnoredErrorHelper $ignoredErrorHelper;

	private string $memoryLimitFile;

	private int $internalErrorsCountLimit;

	public function __construct(
		AnalyserRunner $analyserRunner,
		StubValidator $stubValidator,
		ResultCacheManager $resultCacheManager,
		IgnoredErrorHelper $ignoredErrorHelper,
		string $memoryLimitFile,
		int $internalErrorsCountLimit
	)
	{
		$this->analyserRunner = $analyserRunner;
		$this->stubValidator = $stubValidator;
		$this->resultCacheManager = $resultCacheManager;
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
		InputInterface $input
	): AnalysisResult
	{
		$this->updateMemoryLimitFile();
		$stubErrors = $this->stubValidator->validate();

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

		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			$errors = $ignoredErrorHelperResult->getErrors();
			$warnings = [];
			$internalErrors = [];
			$savedResultCache = false;
		} else {
			$resultCache = $this->resultCacheManager->restore($files, $debug, $errorOutput);
			$intermediateAnalyserResult = $this->runAnalyser(
				$resultCache->getFilesToAnalyse(),
				$files,
				$debug,
				$projectConfigFile,
				$stdOutput,
				$errorOutput,
				$input
			);
			$resultCacheResult = $this->resultCacheManager->process($intermediateAnalyserResult, $resultCache, true);
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

		/** @var bool $runningInParallel */
		$runningInParallel = false;

		if (!$debug) {
			$progressStarted = false;
			$fileOrder = 0;
			$preFileCallback = null;
			$postFileCallback = function (int $step) use ($errorOutput, &$progressStarted, $allAnalysedFilesCount, $filesCount, &$fileOrder): void {
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
		}

		$analyserResult = $this->analyserRunner->runAnalyser($files, $allAnalysedFiles, $preFileCallback, $postFileCallback, $debug, $projectConfigFile, $input);

		if (isset($progressStarted) && $progressStarted) {
			$errorOutput->getStyle()->progressFinish();
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
