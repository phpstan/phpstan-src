<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\AnalysedCodeException;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Collectors\CollectedData;
use PHPStan\Internal\BytesHelper;
use PHPStan\Node\CollectedDataNode;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\PhpDoc\StubValidator;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Input\InputInterface;
use function array_merge;
use function count;
use function memory_get_peak_usage;
use function microtime;
use function sprintf;

class AnalyseApplication
{

	public function __construct(
		private AnalyserRunner $analyserRunner,
		private StubValidator $stubValidator,
		private ResultCacheManagerFactory $resultCacheManagerFactory,
		private IgnoredErrorHelper $ignoredErrorHelper,
		private int $internalErrorsCountLimit,
		private StubFilesProvider $stubFilesProvider,
		private RuleRegistry $ruleRegistry,
		private ScopeFactory $scopeFactory,
		private RuleErrorTransformer $ruleErrorTransformer,
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
		$notFileSpecificErrors = [];
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			$notFileSpecificErrors = $ignoredErrorHelperResult->getErrors();
			$internalErrors = [];
			$collectedData = [];
			$savedResultCache = false;
			$memoryUsageBytes = memory_get_peak_usage(true);
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

			$projectStubFiles = $this->stubFilesProvider->getProjectStubFiles();

			if ($resultCache->isFullAnalysis() && count($projectStubFiles) !== 0) {
				$stubErrors = $this->stubValidator->validate($projectStubFiles, $debug);
				$intermediateAnalyserResult = new AnalyserResult(
					array_merge($intermediateAnalyserResult->getUnorderedErrors(), $stubErrors),
					$intermediateAnalyserResult->getLocallyIgnoredErrors(),
					$intermediateAnalyserResult->getInternalErrors(),
					$intermediateAnalyserResult->getCollectedData(),
					$intermediateAnalyserResult->getDependencies(),
					$intermediateAnalyserResult->getExportedNodes(),
					$intermediateAnalyserResult->hasReachedInternalErrorsCountLimit(),
					$intermediateAnalyserResult->getPeakMemoryUsageBytes(),
				);
			}

			$resultCacheResult = $resultCacheManager->process($intermediateAnalyserResult, $resultCache, $errorOutput, $onlyFiles, true);
			$analyserResult = $resultCacheResult->getAnalyserResult();
			$internalErrors = $analyserResult->getInternalErrors();
			$errors = $analyserResult->getErrors();
			$hasInternalErrors = count($internalErrors) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
			$memoryUsageBytes = $analyserResult->getPeakMemoryUsageBytes();
			$isResultCacheUsed = !$resultCache->isFullAnalysis();

			if (!$hasInternalErrors) {
				foreach ($this->getCollectedDataErrors($analyserResult->getCollectedData(), $onlyFiles) as $error) {
					$errors[] = $error;
				}
			}
			$ignoredErrorHelperProcessedResult = $ignoredErrorHelperResult->process($errors, $onlyFiles, $files, $hasInternalErrors);
			$fileSpecificErrors = $ignoredErrorHelperProcessedResult->getNotIgnoredErrors();
			$notFileSpecificErrors = $ignoredErrorHelperProcessedResult->getOtherIgnoreMessages();
			$collectedData = $analyserResult->getCollectedData();
			$savedResultCache = $resultCacheResult->isSaved();
			if ($analyserResult->hasReachedInternalErrorsCountLimit()) {
				$notFileSpecificErrors[] = sprintf('Reached internal errors count limit of %d, exiting...', $this->internalErrorsCountLimit);
			}
			$notFileSpecificErrors = array_merge($notFileSpecificErrors, $internalErrors);
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
		);
	}

	/**
	 * @param CollectedData[] $collectedData
	 * @return Error[]
	 */
	private function getCollectedDataErrors(array $collectedData, bool $onlyFiles): array
	{
		$nodeType = CollectedDataNode::class;
		$node = new CollectedDataNode($collectedData, $onlyFiles);
		$file = 'N/A';
		$scope = $this->scopeFactory->create(ScopeContext::create($file));
		$errors = [];
		foreach ($this->ruleRegistry->getRules($nodeType) as $rule) {
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
				$errors[] = $this->ruleErrorTransformer->transform($ruleError, $scope, $nodeType, $node->getStartLine());
			}
		}

		return $errors;
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
			return new AnalyserResult([], [], [], [], [], [], false, memory_get_peak_usage(true));
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
