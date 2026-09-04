<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\AnalysedCodeException;
use PHPStan\Analyser\ResultCache\LazyCollectedData;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Registry as RuleRegistry;
use Throwable;
use function array_merge;
use function count;
use function get_class;
use function sprintf;

#[AutowiredService]
final class AnalyserResultFinalizer
{

	/**
	 * @param ExtensionsCollection<IgnoreErrorExtension> $ignoreErrorExtensions
	 */
	public function __construct(
		private RuleRegistry $ruleRegistry,
		#[AutowiredExtensions(of: IgnoreErrorExtension::class)]
		private ExtensionsCollection $ignoreErrorExtensions,
		private RuleErrorTransformer $ruleErrorTransformer,
		private ScopeFactory $scopeFactory,
		private LocalIgnoresProcessor $localIgnoresProcessor,
		#[AutowiredParameter]
		private bool $reportUnmatchedIgnoredErrors,
	)
	{
	}

	public function finalize(AnalyserResult $analyserResult, bool $onlyFiles, bool $debug): FinalizerResult
	{
		if ($analyserResult->getLazyCollectedData()->isEmpty()) {
			return $this->addUnmatchedIgnoredErrors($this->mergeFilteredPhpErrors($analyserResult), [], []);
		}

		$hasInternalErrors = count($analyserResult->getInternalErrors()) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
		if ($hasInternalErrors) {
			return $this->addUnmatchedIgnoredErrors($this->mergeFilteredPhpErrors($analyserResult), [], []);
		}

		try {
			$collectedData = $analyserResult->getCollectedData();
		} catch (Throwable $t) {
			if ($debug) {
				throw $t;
			}

			return $this->addUnmatchedIgnoredErrors($this->mergeFilteredPhpErrors($this->withUnreadableCollectedData($analyserResult, $t)), [], []);
		}

		$nodeType = CollectedDataNode::class;
		// Released with this node; the result below carries the lazy form on.
		$node = new CollectedDataNode($collectedData, $onlyFiles);
		unset($collectedData);

		$file = 'N/A';
		$scope = $this->scopeFactory->create(ScopeContext::create($file));
		$tempCollectorErrors = [];
		$internalErrors = $analyserResult->getInternalErrors();
		foreach ($this->ruleRegistry->getRules($nodeType) as $rule) {
			try {
				$ruleErrors = $rule->processNode($node, $scope);
			} catch (AnalysedCodeException $e) {
				$tempCollectorErrors[] = (new Error($e->getMessage(), $file, $node->getStartLine(), $e, tip: $e->getTip()))
					->withIdentifier('phpstan.internal')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			} catch (IdentifierNotFound $e) {
				$tempCollectorErrors[] = (new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, $node->getStartLine(), $e, tip: 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))
					->withIdentifier('phpstan.reflection')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			} catch (UnableToCompileNode | CircularReference $e) {
				$tempCollectorErrors[] = (new Error(sprintf('Reflection error: %s', $e->getMessage()), $file, $node->getStartLine(), $e))
					->withIdentifier('phpstan.reflection')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			} catch (Throwable $t) {
				if ($debug) {
					throw $t;
				}

				$internalErrors[] = new InternalError(
					$t->getMessage(),
					sprintf('running CollectedDataNode rule %s', get_class($rule)),
					InternalError::prepareTrace($t),
					$t->getTraceAsString(),
					shouldReportBug: true,
				);
				continue;
			}

			foreach ($ruleErrors as $ruleError) {
				$error = $this->ruleErrorTransformer->transform($ruleError, $scope, [], $node);

				if ($error->canBeIgnored()) {
					foreach ($this->ignoreErrorExtensions->getAll() as $ignoreErrorExtension) {
						if ($ignoreErrorExtension->shouldIgnore($error, $node, $scope)) {
							continue 2;
						}
					}
				}

				$tempCollectorErrors[] = $error;
			}
		}

		$errors = $analyserResult->getUnorderedErrors();
		$locallyIgnoredErrors = $analyserResult->getLocallyIgnoredErrors();
		$allLinesToIgnore = $analyserResult->getLinesToIgnore();
		$allUnmatchedLineIgnores = $analyserResult->getUnmatchedLineIgnores();
		$collectorErrors = [];
		$locallyIgnoredCollectorErrors = [];
		foreach ($tempCollectorErrors as $tempCollectorError) {
			$file = $tempCollectorError->getTraitFilePath() ?? $tempCollectorError->getFilePath();
			$linesToIgnore = $allLinesToIgnore[$file] ?? [];
			$unmatchedLineIgnores = $allUnmatchedLineIgnores[$file] ?? [];
			$localIgnoresProcessorResult = $this->localIgnoresProcessor->process(
				[$tempCollectorError],
				$linesToIgnore,
				$unmatchedLineIgnores,
			);
			foreach ($localIgnoresProcessorResult->getFileErrors() as $error) {
				$errors[] = $error;
				$collectorErrors[] = $error;
			}
			foreach ($localIgnoresProcessorResult->getLocallyIgnoredErrors() as $locallyIgnoredError) {
				$locallyIgnoredErrors[] = $locallyIgnoredError;
				$locallyIgnoredCollectorErrors[] = $locallyIgnoredError;
			}
			$allLinesToIgnore[$file] = $localIgnoresProcessorResult->getLinesToIgnore();
			$allUnmatchedLineIgnores[$file] = $localIgnoresProcessorResult->getUnmatchedLineIgnores();
		}

		return $this->addUnmatchedIgnoredErrors(new AnalyserResult(
			unorderedErrors: array_merge($errors, $analyserResult->getFilteredPhpErrors()),
			filteredPhpErrors: [],
			allPhpErrors: $analyserResult->getAllPhpErrors(),
			locallyIgnoredErrors: $locallyIgnoredErrors,
			linesToIgnore: $allLinesToIgnore,
			unmatchedLineIgnores: $allUnmatchedLineIgnores,
			internalErrors: $internalErrors,
			collectedData: $analyserResult->getLazyCollectedData(),
			dependencies: $analyserResult->getDependencies(),
			usedTraitDependencies: $analyserResult->getUsedTraitDependencies(),
			packageDependencies: $analyserResult->getPackageDependencies(),
			exportedNodes: $analyserResult->getExportedNodes(),
			reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
			peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
			processedFiles: $analyserResult->getProcessedFiles(),
			workerCount: $analyserResult->getWorkerCount(),
		), $collectorErrors, $locallyIgnoredCollectorErrors);
	}

	/**
	 * The result cache was discarded by the reader, so the run reports the failure and the collector
	 * rules do not run; the next run analyses everything and starts a new cache.
	 */
	private function withUnreadableCollectedData(AnalyserResult $analyserResult, Throwable $t): AnalyserResult
	{
		$internalErrors = $analyserResult->getInternalErrors();
		$internalErrors[] = new InternalError(
			$t->getMessage(),
			'reading the collected data from the result cache',
			InternalError::prepareTrace($t),
			$t->getTraceAsString(),
			shouldReportBug: false,
		);

		return new AnalyserResult(
			unorderedErrors: $analyserResult->getUnorderedErrors(),
			filteredPhpErrors: $analyserResult->getFilteredPhpErrors(),
			allPhpErrors: $analyserResult->getAllPhpErrors(),
			locallyIgnoredErrors: $analyserResult->getLocallyIgnoredErrors(),
			linesToIgnore: $analyserResult->getLinesToIgnore(),
			unmatchedLineIgnores: $analyserResult->getUnmatchedLineIgnores(),
			internalErrors: $internalErrors,
			collectedData: LazyCollectedData::fromArray([]),
			dependencies: $analyserResult->getDependencies(),
			usedTraitDependencies: $analyserResult->getUsedTraitDependencies(),
			packageDependencies: $analyserResult->getPackageDependencies(),
			exportedNodes: $analyserResult->getExportedNodes(),
			reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
			peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
			processedFiles: $analyserResult->getProcessedFiles(),
			workerCount: $analyserResult->getWorkerCount(),
		);
	}

	private function mergeFilteredPhpErrors(AnalyserResult $analyserResult): AnalyserResult
	{
		return new AnalyserResult(
			unorderedErrors: array_merge($analyserResult->getUnorderedErrors(), $analyserResult->getFilteredPhpErrors()),
			filteredPhpErrors: [],
			allPhpErrors: $analyserResult->getAllPhpErrors(),
			locallyIgnoredErrors: $analyserResult->getLocallyIgnoredErrors(),
			linesToIgnore: $analyserResult->getLinesToIgnore(),
			unmatchedLineIgnores: $analyserResult->getUnmatchedLineIgnores(),
			internalErrors: $analyserResult->getInternalErrors(),
			collectedData: $analyserResult->getLazyCollectedData(),
			dependencies: $analyserResult->getDependencies(),
			usedTraitDependencies: $analyserResult->getUsedTraitDependencies(),
			packageDependencies: $analyserResult->getPackageDependencies(),
			exportedNodes: $analyserResult->getExportedNodes(),
			reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
			peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
			processedFiles: $analyserResult->getProcessedFiles(),
			workerCount: $analyserResult->getWorkerCount(),
		);
	}

	/**
	 * @param list<Error> $collectorErrors
	 * @param list<Error> $locallyIgnoredCollectorErrors
	 */
	private function addUnmatchedIgnoredErrors(
		AnalyserResult $analyserResult,
		array $collectorErrors,
		array $locallyIgnoredCollectorErrors,
	): FinalizerResult
	{
		if (!$this->reportUnmatchedIgnoredErrors) {
			return new FinalizerResult($analyserResult, $collectorErrors, $locallyIgnoredCollectorErrors);
		}

		$errors = $analyserResult->getUnorderedErrors();
		foreach ($analyserResult->getUnmatchedLineIgnores() as $file => $data) {
			foreach ($data as $ignoredFile => $lines) {
				if ($ignoredFile !== $file) {
					continue;
				}

				foreach ($lines as $line => $identifiers) {
					if ($identifiers === null) {
						$errors[] = (new Error(
							sprintf('No error to ignore is reported on line %d.', $line),
							$file,
							$line,
							false,
							$file,
						))->withIdentifier('ignore.unmatchedLine');
						continue;
					}

					foreach ($identifiers as $identifier) {
						$errors[] = (new Error(
							sprintf('No error with identifier %s is reported on line %d.', $identifier['name'], $line),
							$file,
							$line,
							false,
							$file,
						))->withIdentifier('ignore.unmatchedIdentifier');
					}
				}
			}
		}

		return new FinalizerResult(
			new AnalyserResult(
				unorderedErrors: $errors,
				filteredPhpErrors: $analyserResult->getFilteredPhpErrors(),
				allPhpErrors: $analyserResult->getAllPhpErrors(),
				locallyIgnoredErrors: $analyserResult->getLocallyIgnoredErrors(),
				linesToIgnore: $analyserResult->getLinesToIgnore(),
				unmatchedLineIgnores: $analyserResult->getUnmatchedLineIgnores(),
				internalErrors: $analyserResult->getInternalErrors(),
				collectedData: $analyserResult->getLazyCollectedData(),
				dependencies: $analyserResult->getDependencies(),
				usedTraitDependencies: $analyserResult->getUsedTraitDependencies(),
				packageDependencies: $analyserResult->getPackageDependencies(),
				exportedNodes: $analyserResult->getExportedNodes(),
				reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
				peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
				processedFiles: $analyserResult->getProcessedFiles(),
				workerCount: $analyserResult->getWorkerCount(),
			),
			$collectorErrors,
			$locallyIgnoredCollectorErrors,
		);
	}

}
