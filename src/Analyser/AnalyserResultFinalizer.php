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
	)
	{
	}

	public function finalize(AnalyserResult $analyserResult, bool $onlyFiles): AnalyserResult
	{
		if (count($analyserResult->getCollectedData()) === 0) {
			return $analyserResult;
		}

		$hasInternalErrors = count($analyserResult->getInternalErrors()) > 0 || $analyserResult->hasReachedInternalErrorsCountLimit();
		if ($hasInternalErrors) {
			return $analyserResult;
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
