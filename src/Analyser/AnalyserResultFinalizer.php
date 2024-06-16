<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\AnalysedCodeException;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Registry as RuleRegistry;
use function array_merge;
use function count;
use function sprintf;

class AnalyserResultFinalizer
{

	public function __construct(
		private RuleRegistry $ruleRegistry,
		private RuleErrorTransformer $ruleErrorTransformer,
		private ScopeFactory $scopeFactory,
		private LocalIgnoresProcessor $localIgnoresProcessor,
		private bool $reportUnmatchedIgnoredErrors,
	)
	{
	}

	public function finalize(AnalyserResult $analyserResult, bool $onlyFiles): FinalizerResult
	{
		if (count($analyserResult->getCollectedData()) === 0) {
			return $this->addUnmatchedIgnoredErrors($this->mergeFilteredPhpErrors($analyserResult), [], []);
		}

		$hasInternalErrors = count($analyserResult->getInternalErrors()) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
		if ($hasInternalErrors) {
			return $this->addUnmatchedIgnoredErrors($this->mergeFilteredPhpErrors($analyserResult), [], []);
		}

		$nodeType = CollectedDataNode::class;
		$node = new CollectedDataNode($analyserResult->getCollectedData(), $onlyFiles);

		$file = 'N/A';
		$scope = $this->scopeFactory->create(ScopeContext::create($file));
		$tempCollectorErrors = [];
		foreach ($this->ruleRegistry->getRules($nodeType) as $rule) {
			try {
				$ruleErrors = $rule->processNode($node, $scope);
			} catch (AnalysedCodeException $e) {
				$tempCollectorErrors[] = (new Error($e->getMessage(), $file, $node->getStartLine(), $e, null, null, $e->getTip()))
					->withIdentifier('phpstan.internal')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
						InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString(),
					]);
				continue;
			} catch (IdentifierNotFound $e) {
				$tempCollectorErrors[] = (new Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, $node->getStartLine(), $e, null, null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection');
				continue;
			} catch (UnableToCompileNode | CircularReference $e) {
				$tempCollectorErrors[] = (new Error(sprintf('Reflection error: %s', $e->getMessage()), $file, $node->getStartLine(), $e))->withIdentifier('phpstan.reflection');
				continue;
			}

			foreach ($ruleErrors as $ruleError) {
				$tempCollectorErrors[] = $this->ruleErrorTransformer->transform($ruleError, $scope, $nodeType, $node->getStartLine());
			}
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

		return $this->addUnmatchedIgnoredErrors(new AnalyserResult(
			array_merge($errors, $analyserResult->getFilteredPhpErrors()),
			[],
			$analyserResult->getAllPhpErrors(),
			$locallyIgnoredErrors,
			$allLinesToIgnore,
			$allUnmatchedLineIgnores,
			$analyserResult->getInternalErrors(),
			$analyserResult->getCollectedData(),
			$analyserResult->getDependencies(),
			$analyserResult->getExportedNodes(),
			$analyserResult->hasReachedInternalErrorsCountLimit(),
			$analyserResult->getPeakMemoryUsageBytes(),
		), $collectorErrors, $locallyIgnoredCollectorErrors);
	}

	private function mergeFilteredPhpErrors(AnalyserResult $analyserResult): AnalyserResult
	{
		return new AnalyserResult(
			array_merge($analyserResult->getUnorderedErrors(), $analyserResult->getFilteredPhpErrors()),
			[],
			$analyserResult->getAllPhpErrors(),
			$analyserResult->getLocallyIgnoredErrors(),
			$analyserResult->getLinesToIgnore(),
			$analyserResult->getUnmatchedLineIgnores(),
			$analyserResult->getInternalErrors(),
			$analyserResult->getCollectedData(),
			$analyserResult->getDependencies(),
			$analyserResult->getExportedNodes(),
			$analyserResult->hasReachedInternalErrorsCountLimit(),
			$analyserResult->getPeakMemoryUsageBytes(),
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
							sprintf('No error with identifier %s is reported on line %d.', $identifier, $line),
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
				$errors,
				$analyserResult->getFilteredPhpErrors(),
				$analyserResult->getAllPhpErrors(),
				$analyserResult->getLocallyIgnoredErrors(),
				$analyserResult->getLinesToIgnore(),
				$analyserResult->getUnmatchedLineIgnores(),
				$analyserResult->getInternalErrors(),
				$analyserResult->getCollectedData(),
				$analyserResult->getDependencies(),
				$analyserResult->getExportedNodes(),
				$analyserResult->hasReachedInternalErrorsCountLimit(),
				$analyserResult->getPeakMemoryUsageBytes(),
			),
			$collectorErrors,
			$locallyIgnoredCollectorErrors,
		);
	}

}
