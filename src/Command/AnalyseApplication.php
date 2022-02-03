<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\Internal\BytesHelper;
use PHPStan\Internal\ConsumptionTrackingCollector;
use PHPStan\Internal\TimeHelper;
use PHPStan\PhpDoc\StubValidator;
use Symfony\Component\Console\Input\InputInterface;
use function array_merge;
use function count;
use function is_string;
use function sprintf;

class AnalyseApplication
{

	public function __construct(
		private AnalyserRunner $analyserRunner,
		private StubValidator $stubValidator,
		private ResultCacheManagerFactory $resultCacheManagerFactory,
		private IgnoredErrorHelper $ignoredErrorHelper,
		private int $internalErrorsCountLimit,
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
		$resultCacheManager = $this->resultCacheManagerFactory->create([]);

		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			$errors = $ignoredErrorHelperResult->getErrors();
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
				$input,
			);

			$projectStubFiles = [];
			if ($projectConfigArray !== null) {
				$projectStubFiles = $projectConfigArray['parameters']['stubFiles'] ?? [];
			}
			if ($resultCache->isFullAnalysis() && count($projectStubFiles) !== 0) {
				$stubErrors = $this->stubValidator->validate($projectStubFiles, $debug);
				$intermediateAnalyserResult = new AnalyserResult(
					array_merge($intermediateAnalyserResult->getErrors(), $stubErrors),
					$intermediateAnalyserResult->getInternalErrors(),
					$intermediateAnalyserResult->getDependencies(),
					$intermediateAnalyserResult->getExportedNodes(),
					$intermediateAnalyserResult->hasReachedInternalErrorsCountLimit(),
				);
			}

			$resultCacheResult = $resultCacheManager->process($intermediateAnalyserResult, $resultCache, $errorOutput, $onlyFiles, true);
			$analyserResult = $resultCacheResult->getAnalyserResult();
			$internalErrors = $analyserResult->getInternalErrors();
			$errors = $ignoredErrorHelperResult->process($analyserResult->getErrors(), $onlyFiles, $files, count($internalErrors) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit());
			$savedResultCache = $resultCacheResult->isSaved();
			if ($analyserResult->hasReachedInternalErrorsCountLimit()) {
				$errors[] = sprintf('Reached internal errors count limit of %d, exiting...', $this->internalErrorsCountLimit);
			}
			$errors = array_merge($errors, $internalErrors);
		}

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
			[],
			$defaultLevelUsed,
			$projectConfigFile,
			$savedResultCache,
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
			return new AnalyserResult([], [], [], [], false);
		}

		$consumptionCollector = null;
		if ($stdOutput->isDebug()) {
			// use collector whenever phpstan runs with -vvv
			$consumptionCollector = new ConsumptionTrackingCollector();
		}
		if (!$debug) {
			$progressStarted = false;
			$preFileCallback = null;
			$postFileCallback = static function (int $step) use ($errorOutput, &$progressStarted, $allAnalysedFilesCount, $filesCount): void {
				if (!$progressStarted) {
					$errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
					$errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount - $filesCount);
					$progressStarted = true;
				}
				$errorOutput->getStyle()->progressAdvance($step);
			};
		} else {
			$preFileCallback = static function (string $file) use ($stdOutput): void {
				$stdOutput->writeLineFormatted($file);
			};
			$postFileCallback = null;
			if ($stdOutput->isDebug()) {
				$postFileCallback = static function () use ($stdOutput, $consumptionCollector): void {
					if ($consumptionCollector === null) {
						return;
					}
					$stdOutput->writeLineFormatted(
						sprintf(
							'--- consumed %s, total %s, took %s',
							BytesHelper::bytes($consumptionCollector->getMemoryConsumedForLatestFile()),
							BytesHelper::bytes($consumptionCollector->getTotalMemoryConsumed()),
							TimeHelper::humaniseFractionalSeconds($consumptionCollector->getTimeConsumedForLatestFile()),
						),
					);
				};
			}
		}

		$analyserResult = $this->analyserRunner->runAnalyser(
			$files,
			$allAnalysedFiles,
			$preFileCallback,
			$postFileCallback,
			$debug,
			true,
			$projectConfigFile,
			null,
			null,
			$input,
			$consumptionCollector,
		);

		if (isset($progressStarted) && $progressStarted) {
			$errorOutput->getStyle()->progressFinish();
		}

		if ($stdOutput->isDebug() && isset($consumptionCollector)) {
			$stdOutput->writeLineFormatted('');
			if ($debug) {
				// top memory can be only be determined when not running in parallel
				// so we just say: only in debug mode
				$stdOutput->writeLineFormatted('Top memory consumers:');
				$rows = [];
				foreach ($consumptionCollector->getHumanisedTopMemoryConsumers() as $file => $consumption) {
					$rows[] = [$file, $consumption];
				}
				$stdOutput->getStyle()->table(['file', 'memory consumed'], $rows);
			}
			$stdOutput->writeLineFormatted('Top time consumers:');
			$rows = [];
			foreach ($consumptionCollector->getHumanisedTopTimeConsumers() as $file => $consumption) {
				$rows[] = [ $file, $consumption ];
			}
			$stdOutput->getStyle()->table(['file', 'time to process'], $rows);
		}

		return $analyserResult;
	}

}
