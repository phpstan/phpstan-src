<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\AnalysedCodeException;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Registry as RuleRegistry;
use function count;
use function sprintf;

class AnalyserResultFinalizer
{

	public function __construct(
		private RuleRegistry $ruleRegistry,
		private RuleErrorTransformer $ruleErrorTransformer,
		private ScopeFactory $scopeFactory,
		private bool $reportUnmatchedIgnoredErrors,
	)
	{
	}

	public function finalize(AnalyserResult $analyserResult, bool $onlyFiles): AnalyserResult
	{
		if (count($analyserResult->getCollectedData()) === 0) {
			return $this->addUnmatchedIgnoredErrors($analyserResult);
		}

		$hasInternalErrors = count($analyserResult->getInternalErrors()) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
		if ($hasInternalErrors) {
			return $this->addUnmatchedIgnoredErrors($analyserResult);
		}

		$nodeType = CollectedDataNode::class;
		$node = new CollectedDataNode($analyserResult->getCollectedData(), $onlyFiles);

		$file = 'N/A';
		$scope = $this->scopeFactory->create(ScopeContext::create($file));
		$errors = $analyserResult->getUnorderedErrors();
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

		return $this->addUnmatchedIgnoredErrors(new AnalyserResult(
			$errors,
			$analyserResult->getLocallyIgnoredErrors(),
			$analyserResult->getLinesToIgnore(),
			$analyserResult->getUnmatchedLineIgnores(),
			$analyserResult->getInternalErrors(),
			$analyserResult->getCollectedData(),
			$analyserResult->getDependencies(),
			$analyserResult->getExportedNodes(),
			$analyserResult->hasReachedInternalErrorsCountLimit(),
			$analyserResult->getPeakMemoryUsageBytes(),
		));
	}

	private function addUnmatchedIgnoredErrors(AnalyserResult $analyserResult): AnalyserResult
	{
		if (!$this->reportUnmatchedIgnoredErrors) {
			return $analyserResult;
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

		return new AnalyserResult(
			$errors,
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

}
