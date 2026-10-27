<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PHPStan\AnalysedCodeException;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Node\CollectedDataNodeReads;
use PHPStan\Rules\Registry as RuleRegistry;
use Throwable;
use function array_key_exists;
use function array_merge;
use function count;
use function get_class;
use function ksort;
use function md5;
use function serialize;
use function sprintf;

#[AutowiredService]
final class AnalyserResultFinalizer
{

	/** The file of an error a rule on CollectedDataNode reports without one. */
	public const NO_FILE = 'N/A';

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
		private ValueDependencyCollector $valueDependencyCollector,
		#[AutowiredParameter(ref: '%featureToggles.reuseCollectedDataRuleErrors%')]
		private bool $reuseCollectedDataRuleErrors,
	)
	{
	}

	/**
	 * @param Closure(string $ruleClass): void|null $preRuleCallback called only for a rule that runs, not for one whose errors are reused
	 * @param Closure(): void|null $postRuleCallback called when the rule's processNode() has returned or thrown, before its errors are processed
	 * @param array<string, CollectedDataRuleResult> $previousCollectedDataRuleResults the rule results of the
	 *        previous run, from the result cache - a rule whose input did not change reuses its errors.
	 *        Only with the reuseCollectedDataRuleErrors feature toggle, otherwise every rule runs.
	 */
	public function finalize(
		AnalyserResult $analyserResult,
		bool $onlyFiles,
		bool $debug,
		?Closure $preRuleCallback = null,
		?Closure $postRuleCallback = null,
		array $previousCollectedDataRuleResults = [],
	): FinalizerResult
	{
		if (count($analyserResult->getCollectedData()) === 0) {
			return $this->addUnmatchedIgnoredErrors($this->mergeFilteredPhpErrors($analyserResult), [], []);
		}

		$hasInternalErrors = count($analyserResult->getInternalErrors()) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
		if ($hasInternalErrors) {
			return $this->addUnmatchedIgnoredErrors($this->mergeFilteredPhpErrors($analyserResult), [], []);
		}

		$nodeType = CollectedDataNode::class;
		$collectedData = $analyserResult->getCollectedData();
		$node = new CollectedDataNode($collectedData, $onlyFiles);

		// Hashed once per collector type and run, in the same order however the files were merged.
		$collectorHashes = [];
		$hashCollector = static function (string $collectorType) use ($collectedData, &$collectorHashes): string {
			if (!array_key_exists($collectorType, $collectorHashes)) {
				$data = [];
				foreach ($collectedData as $filePath => $collectedDataPerCollector) {
					if (!isset($collectedDataPerCollector[$collectorType])) {
						continue;
					}

					$data[$filePath] = $collectedDataPerCollector[$collectorType];
				}
				ksort($data);
				$collectorHashes[$collectorType] = md5(serialize($data));
			}

			return $collectorHashes[$collectorType];
		};

		$file = self::NO_FILE;
		$scope = $this->scopeFactory->create(ScopeContext::create($file));
		$tempCollectorErrors = [];
		$internalErrors = $analyserResult->getInternalErrors();
		$collectedDataRuleResults = [];
		$reusedRulesCount = 0;
		$ruleCountsByClass = [];
		$currentValues = [];
		foreach ($this->ruleRegistry->getRules($nodeType) as $rule) {
			$ruleClass = get_class($rule);
			$ruleCountsByClass[$ruleClass] = ($ruleCountsByClass[$ruleClass] ?? 0) + 1;
			$ruleKey = sprintf('%s#%d', $ruleClass, $ruleCountsByClass[$ruleClass]);

			$previousResult = $this->reuseCollectedDataRuleErrors ? $previousCollectedDataRuleResults[$ruleKey] ?? null : null;
			if ($previousResult !== null && $this->canReuse($previousResult, $onlyFiles, $hashCollector, $currentValues)) {
				$collectedDataRuleResults[$ruleKey] = $previousResult;
				$reusedRulesCount++;
				$this->addRuleErrors($previousResult->getErrors(), $node, $scope, $tempCollectorErrors);
				continue;
			}

			$reads = new CollectedDataNodeReads();
			$ruleNode = new CollectedDataNode($collectedData, $onlyFiles, $reads);
			if ($this->reuseCollectedDataRuleErrors) {
				$this->valueDependencyCollector->startCollectedDataRule();
			}
			if ($preRuleCallback !== null) {
				$preRuleCallback($ruleClass);
			}
			try {
				$ruleErrors = $rule->processNode($ruleNode, $scope);
				$ruleValueDependencies = $this->valueDependencyCollector->getCollectedDataRuleValues();
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
			} finally {
				$this->valueDependencyCollector->finishCollectedDataRule();
				if ($postRuleCallback !== null) {
					$postRuleCallback();
				}
			}

			$errors = [];
			foreach ($ruleErrors as $ruleError) {
				$errors[] = $this->ruleErrorTransformer->transform($ruleError, $scope, [], $ruleNode);
			}

			if ($this->reuseCollectedDataRuleErrors) {
				$collectedDataRuleResults[$ruleKey] = $this->createRuleResult($reads, $onlyFiles, $hashCollector, $errors, $ruleValueDependencies);
			}

			$this->addRuleErrors($errors, $node, $scope, $tempCollectorErrors);
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
			collectedData: $analyserResult->getCollectedData(),
			dependencies: $analyserResult->getDependencies(),
			usedTraitDependencies: $analyserResult->getUsedTraitDependencies(),
			valueDependencies: $analyserResult->getValueDependencies(),
			packageDependencies: $analyserResult->getPackageDependencies(),
			exportedNodes: $analyserResult->getExportedNodes(),
			reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
			peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
			processedFiles: $analyserResult->getProcessedFiles(),
			workerCount: $analyserResult->getWorkerCount(),
		), $collectorErrors, $locallyIgnoredCollectorErrors, $collectedDataRuleResults, $reusedRulesCount);
	}

	/**
	 * @param Closure(string): string $hashCollector
	 * @param list<Error> $errors
	 * @param array<string, array{string, string, string}> $valueDependencies
	 */
	private function createRuleResult(CollectedDataNodeReads $reads, bool $onlyFiles, Closure $hashCollector, array $errors, array $valueDependencies): CollectedDataRuleResult
	{
		$readCollectorHashes = [];
		foreach ($reads->getCollectorTypes() as $collectorType) {
			$readCollectorHashes[$collectorType] = $hashCollector($collectorType);
		}

		$storedValueDependencies = [];
		foreach ($valueDependencies as [$extensionClass, $key, $value]) {
			$extension = $this->valueDependencyCollector->getExtension($extensionClass);
			if ($extension === null) {
				continue;
			}

			$storedValueDependencies[] = [$extensionClass, $extension->keyToResultCache($key), $value];
		}

		return new CollectedDataRuleResult(
			$readCollectorHashes,
			$reads->isOnlyFilesRead() ? $onlyFiles : null,
			$errors,
			$storedValueDependencies,
		);
	}

	/**
	 * @param Closure(string): string $hashCollector
	 * @param array<string, string> $currentValues id => value, asked for once per run - every rule shares the values of the classes
	 */
	private function canReuse(CollectedDataRuleResult $previousResult, bool $onlyFiles, Closure $hashCollector, array &$currentValues): bool
	{
		$previousOnlyFiles = $previousResult->getOnlyFiles();
		if ($previousOnlyFiles !== null && $previousOnlyFiles !== $onlyFiles) {
			return false;
		}

		foreach ($previousResult->getCollectorHashes() as $collectorType => $hash) {
			if ($hashCollector($collectorType) !== $hash) {
				return false;
			}
		}

		// the values the rule declared through DependencyTracker - see ValueDependencyCollector
		foreach ($previousResult->getValueDependencies() as [$extensionClass, $storedKey, $value]) {
			$extension = $this->valueDependencyCollector->getExtension($extensionClass);
			if ($extension === null) {
				return false;
			}

			$id = ValueDependencyCollector::getId($extensionClass, $storedKey);
			$currentValues[$id] ??= $extension->getValue($extension->keyFromResultCache($storedKey));
			if ($currentValues[$id] !== $value) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param list<Error> $errors
	 * @param list<Error> $tempCollectorErrors
	 */
	private function addRuleErrors(array $errors, CollectedDataNode $node, Scope $scope, array &$tempCollectorErrors): void
	{
		foreach ($errors as $error) {
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
			collectedData: $analyserResult->getCollectedData(),
			dependencies: $analyserResult->getDependencies(),
			usedTraitDependencies: $analyserResult->getUsedTraitDependencies(),
			valueDependencies: $analyserResult->getValueDependencies(),
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
	 * @param array<string, CollectedDataRuleResult> $collectedDataRuleResults
	 */
	private function addUnmatchedIgnoredErrors(
		AnalyserResult $analyserResult,
		array $collectorErrors,
		array $locallyIgnoredCollectorErrors,
		array $collectedDataRuleResults = [],
		int $reusedRulesCount = 0,
	): FinalizerResult
	{
		if (!$this->reportUnmatchedIgnoredErrors) {
			return new FinalizerResult($analyserResult, $collectorErrors, $locallyIgnoredCollectorErrors, $collectedDataRuleResults, $reusedRulesCount);
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
				collectedData: $analyserResult->getCollectedData(),
				dependencies: $analyserResult->getDependencies(),
				usedTraitDependencies: $analyserResult->getUsedTraitDependencies(),
				valueDependencies: $analyserResult->getValueDependencies(),
				packageDependencies: $analyserResult->getPackageDependencies(),
				exportedNodes: $analyserResult->getExportedNodes(),
				reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
				peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
				processedFiles: $analyserResult->getProcessedFiles(),
				workerCount: $analyserResult->getWorkerCount(),
			),
			$collectorErrors,
			$locallyIgnoredCollectorErrors,
			$collectedDataRuleResults,
			$reusedRulesCount,
		);
	}

}
