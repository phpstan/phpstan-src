<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PHPStan\AnalysedCodeException;
use PHPStan\Analyser\Ignore\LineIgnoreFixer;
use PHPStan\Analyser\Ignore\UnmatchedIgnoreFixer;
use PHPStan\Analyser\ResultCache\AppearedSymbols;
use PHPStan\Analyser\ResultCache\LazyCollectedData;
use PHPStan\Analyser\ResultCache\UnreadableCollectedDataException;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\File\FileReader;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Node\CollectedDataNodeReads;
use PHPStan\Rules\Registry as RuleRegistry;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function get_class;
use function hash_file;
use function is_file;
use function ksort;
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
		private UnmatchedIgnoreFixer $unmatchedIgnoreFixer,
		private LineIgnoreFixer $lineIgnoreFixer,
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
	 *        Only with the reuseCollectedDataRuleErrors feature toggle, otherwise every rule runs. A rule that
	 *        does not call get() on the node always runs.
	 * @param AppearedSymbols|null $appearedSymbols what may have been declared since they were saved, see
	 *        ResultCache::getAppearedSymbols() - anything when null
	 */
	public function finalize(
		AnalyserResult $analyserResult,
		bool $onlyFiles,
		bool $debug,
		?Closure $preRuleCallback = null,
		?Closure $postRuleCallback = null,
		array $previousCollectedDataRuleResults = [],
		?AppearedSymbols $appearedSymbols = null,
	): FinalizerResult
	{
		$appearedSymbols ??= AppearedSymbols::anything();
		if ($analyserResult->getLazyCollectedData()->isEmpty()) {
			return $this->processInlineIgnores($this->mergeFilteredPhpErrors($analyserResult), [], []);
		}

		$hasInternalErrors = count($analyserResult->getInternalErrors()) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
		if ($hasInternalErrors) {
			return $this->processInlineIgnores($this->mergeFilteredPhpErrors($analyserResult), [], []);
		}

		// Not decoded here: each rule reads the collectors it asks for, one file at a time if it iterates.
		$collectedData = $analyserResult->getLazyCollectedData();
		$nodeType = CollectedDataNode::class;
		$node = new CollectedDataNode($collectedData, $onlyFiles);

		// Hashed once per collector type and run, file by file, so that no collector's data is held as one string.
		$collectorHashes = [];
		$hashCollector = static function (string $collectorType) use ($collectedData, &$collectorHashes): string {
			if (!array_key_exists($collectorType, $collectorHashes)) {
				$collectorHashes[$collectorType] = $collectedData->hashCollector($collectorType);
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
		$collectedDataRuleSkipped = false;
		try {
			foreach ($this->ruleRegistry->getRules($nodeType) as $rule) {
				$ruleClass = get_class($rule);
				$ruleCountsByClass[$ruleClass] = ($ruleCountsByClass[$ruleClass] ?? 0) + 1;
				$ruleKey = sprintf('%s#%d', $ruleClass, $ruleCountsByClass[$ruleClass]);

				$previousResult = $this->reuseCollectedDataRuleErrors ? $previousCollectedDataRuleResults[$ruleKey] ?? null : null;
				if ($previousResult !== null && $this->canReuse($previousResult, $onlyFiles, $hashCollector, $appearedSymbols, $currentValues)) {
					$collectedDataRuleResults[$ruleKey] = $previousResult;
					$reusedRulesCount++;
					$collectedDataRuleSkipped = $collectedDataRuleSkipped || $previousResult->getOnlyFiles() === true;
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
					// a rule that never asked for collected data works with something else, so it runs
					// every time - its result is not kept, and neither are the values it depends on
					$isReusable = $this->reuseCollectedDataRuleErrors && $reads->getCollectorTypes() !== [];
					$ruleValueDependencies = $isReusable ? $this->valueDependencyCollector->getCollectedDataRuleValues() : [];
					// the rule may have caught the read error and reported errors from partial data
					$unreadableException = $collectedData->getUnreadableException();
					if ($unreadableException !== null) {
						throw $unreadableException;
					}
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
				} catch (UnreadableCollectedDataException $e) {
					throw $e;
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
					// the rule may have returned early, like NotAnalysedTraitRule does
					$collectedDataRuleSkipped = $collectedDataRuleSkipped || ($onlyFiles && $reads->isOnlyFilesRead());
					$this->valueDependencyCollector->finishCollectedDataRule();
					if ($postRuleCallback !== null) {
						$postRuleCallback();
					}
				}

				$errors = [];
				foreach ($ruleErrors as $ruleError) {
					$errors[] = $this->ruleErrorTransformer->transform($ruleError, $scope, [], $ruleNode);
				}

				if ($isReusable) {
					$collectedDataRuleResults[$ruleKey] = $this->createRuleResult($reads, $onlyFiles, $hashCollector, $errors, $ruleValueDependencies);
				}

				$this->addRuleErrors($errors, $node, $scope, $tempCollectorErrors);
			}
		} catch (UnreadableCollectedDataException $e) {
			if ($debug) {
				throw $e;
			}

			return $this->processInlineIgnores($this->mergeFilteredPhpErrors($this->withUnreadableCollectedData($analyserResult, $internalErrors, $e)), [], []);
		}

		$errors = $analyserResult->getUnorderedErrors();
		$locallyIgnoredErrors = $analyserResult->getLocallyIgnoredErrors();
		$allLinesToIgnore = $analyserResult->getLinesToIgnore();
		$allUnmatchedLineIgnores = $analyserResult->getUnmatchedLineIgnores();
		$collectorErrors = [];
		$locallyIgnoredCollectorErrors = [];
		foreach ($tempCollectorErrors as $tempCollectorError) {
			$file = $tempCollectorError->getFilePath();
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

		return $this->processInlineIgnores(new AnalyserResult(
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
			valueDependencies: $analyserResult->getValueDependencies(),
			packageDependencies: $analyserResult->getPackageDependencies(),
			exportedNodes: $analyserResult->getExportedNodes(),
			reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
			peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
			processedFiles: $analyserResult->getProcessedFiles(),
			workerCount: $analyserResult->getWorkerCount(),
		), $collectorErrors, $locallyIgnoredCollectorErrors, $collectedDataRuleResults, $reusedRulesCount, $collectedDataRuleSkipped);
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
	private function canReuse(CollectedDataRuleResult $previousResult, bool $onlyFiles, Closure $hashCollector, AppearedSymbols $appearedSymbols, array &$currentValues): bool
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
			$key = $extension->keyFromResultCache($storedKey);
			$currentValues[$id] ??= $value === ValueDependencyCollector::MISSING && $appearedSymbols->isStillMissing($extensionClass, $key)
				? ValueDependencyCollector::MISSING
				: $extension->getValue($key);
			if ($currentValues[$id] !== $value) {
				return false;
			}
		}

		// a fix is a diff against the file contents, which can change without changing the collected data
		foreach ($previousResult->getErrors() as $error) {
			foreach ($error->getFixedErrorDiffs() as $fixedErrorDiff) {
				if (!is_file($fixedErrorDiff->file) || hash_file('sha256', $fixedErrorDiff->file) !== $fixedErrorDiff->originalHash) {
					return false;
				}
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

	/**
	 * The result cache was discarded by the reader, so the run reports the failure and no errors of the
	 * rules on CollectedDataNode; the next run analyses everything and starts a new cache.
	 *
	 * @param list<InternalError> $internalErrors including the crashes of the rules that ran before the read failed
	 */
	private function withUnreadableCollectedData(AnalyserResult $analyserResult, array $internalErrors, Throwable $t): AnalyserResult
	{
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
			valueDependencies: $analyserResult->getValueDependencies(),
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
	 * Replaces each ignore of all errors on a line with an ignore of the identifiers of the errors reported there,
	 * including the errors of CollectedDataNode rules and the errors of a trait in the contexts of the classes using it.
	 *
	 * @param list<Error> $errors
	 * @return list<Error>
	 */
	private function fixAllLineErrorsIgnores(array $errors): array
	{
		$allLineErrors = [];
		foreach ($errors as $error) {
			if ($error->getIdentifier() !== 'ignore.allLineErrors' || $error->getLine() === null) {
				continue;
			}

			$allLineErrors[$error->getFile()][$error->getLine()] = true;
		}
		if ($allLineErrors === []) {
			return $errors;
		}

		// an error in a trait is reported in the context of each class using it, and each context
		// matches the errors against its own copy of the ignore, so the identifiers are grouped by context
		$contextIdentifiers = [];
		foreach ($errors as $error) {
			$line = $error->getLine();
			$file = $error->getTraitFilePath() ?? $error->getFile();
			if ($line === null || !isset($allLineErrors[$file][$line]) || !$error->canBeIgnored()) {
				continue;
			}

			$identifier = $error->getIdentifier();
			if ($identifier === null) {
				// such an error cannot be ignored by an identifier
				$contextIdentifiers[$file][$line] = false;
			} elseif (($contextIdentifiers[$file][$line] ?? null) !== false) {
				$contextIdentifiers[$file][$line][$error->getFile()][] = $identifier;
			}
		}

		$fixes = [];
		foreach ($contextIdentifiers as $file => $lines) {
			if (!is_file($file)) {
				continue;
			}

			$code = FileReader::read($file);
			foreach ($lines as $line => $identifiersByContext) {
				if ($identifiersByContext === false) {
					continue;
				}

				// each identifier as many times as the context that reports it the most,
				// with the contexts sorted for the same result whatever order the files were analysed in
				ksort($identifiersByContext);
				$lineIdentifiers = [];
				$counts = [];
				foreach ($identifiersByContext as $identifiers) {
					$contextCounts = [];
					foreach ($identifiers as $identifier) {
						$contextCounts[$identifier] = ($contextCounts[$identifier] ?? 0) + 1;
						if ($contextCounts[$identifier] <= ($counts[$identifier] ?? 0)) {
							continue;
						}

						$counts[$identifier] = $contextCounts[$identifier];
						$lineIdentifiers[] = $identifier;
					}
				}
				if ($lineIdentifiers === []) {
					continue;
				}

				$newCode = $this->lineIgnoreFixer->fix($code, $line, $lineIdentifiers);
				if ($newCode === null) {
					continue;
				}

				$fixes[$file][$line] = $this->ruleErrorTransformer->createFixedErrorDiff($file, $code, $newCode);
			}
		}

		return array_map(static function (Error $error) use ($fixes): Error {
			$line = $error->getLine();
			if ($line === null || $error->getIdentifier() !== 'ignore.allLineErrors' || !isset($fixes[$error->getFile()][$line])) {
				return $error;
			}

			return new Error(
				$error->getMessage(),
				$error->getFile(),
				$line,
				false,
				$error->getFilePath(),
				tip: $error->getTip(),
				identifier: 'ignore.allLineErrors',
				fixedErrorDiffs: [$fixes[$error->getFile()][$line]],
			);
		}, $errors);
	}

	/**
	 * @return list<FixedErrorDiff>
	 */
	private function fixUnmatchedIgnore(string $file, ?string $code, int $line, ?string $identifier): array
	{
		if ($code === null) {
			return [];
		}

		$newCode = $this->unmatchedIgnoreFixer->fix($code, $line, $identifier);
		if ($newCode === null) {
			return [];
		}

		return [$this->ruleErrorTransformer->createFixedErrorDiff($file, $code, $newCode)];
	}

	/**
	 * Fixes ignores of all errors on a line, and reports unmatched inline ignores. Runs after the errors of CollectedDataNode rules
	 * are known, because they can be reported on the ignored lines too.
	 *
	 * @param list<Error> $collectorErrors
	 * @param list<Error> $locallyIgnoredCollectorErrors
	 * @param array<string, CollectedDataRuleResult> $collectedDataRuleResults
	 */
	private function processInlineIgnores(
		AnalyserResult $analyserResult,
		array $collectorErrors,
		array $locallyIgnoredCollectorErrors,
		array $collectedDataRuleResults = [],
		int $reusedRulesCount = 0,
		bool $collectedDataRuleSkipped = false,
	): FinalizerResult
	{
		$errors = $this->fixAllLineErrorsIgnores($analyserResult->getUnorderedErrors());
		// a rule that skipped its errors because only files are analysed may have left the ignores of them unmatched
		if (!$this->reportUnmatchedIgnoredErrors || $collectedDataRuleSkipped) {
			$analyserResult = new AnalyserResult(
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
				valueDependencies: $analyserResult->getValueDependencies(),
				packageDependencies: $analyserResult->getPackageDependencies(),
				exportedNodes: $analyserResult->getExportedNodes(),
				reachedInternalErrorsCountLimit: $analyserResult->hasReachedInternalErrorsCountLimit(),
				peakMemoryUsageBytes: $analyserResult->getPeakMemoryUsageBytes(),
				processedFiles: $analyserResult->getProcessedFiles(),
				workerCount: $analyserResult->getWorkerCount(),
			);

			return new FinalizerResult($analyserResult, $collectorErrors, $locallyIgnoredCollectorErrors, $collectedDataRuleResults, $reusedRulesCount, $collectedDataRuleSkipped);
		}

		foreach ($analyserResult->getUnmatchedLineIgnores() as $file => $data) {
			foreach ($data as $ignoredFile => $lines) {
				if ($ignoredFile !== $file) {
					continue;
				}

				$code = is_file($file) ? FileReader::read($file) : null;
				foreach ($lines as $line => $identifiers) {
					if ($identifiers === null) {
						$errors[] = new Error(
							sprintf('No error to ignore is reported on line %d.', $line),
							$file,
							$line,
							false,
							$file,
							identifier: 'ignore.unmatchedLine',
							fixedErrorDiffs: $this->fixUnmatchedIgnore($file, $code, $line, null),
						);
						continue;
					}

					foreach ($identifiers as $identifier) {
						$errors[] = new Error(
							sprintf('No error with identifier %s is reported on line %d.', $identifier['name'], $line),
							$file,
							$line,
							false,
							$file,
							identifier: 'ignore.unmatchedIdentifier',
							fixedErrorDiffs: $this->fixUnmatchedIgnore($file, $code, $line, $identifier['name']),
						);
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
			$collectedDataRuleSkipped,
		);
	}

}
