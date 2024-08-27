<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use function count;

/**
 * @implements Collector<MethodReturnStatementsNode, array{class-string, string, string}>
 */
final class MethodWithoutImpurePointsCollector implements Collector
{

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		$method = $node->getMethodReflection();
		if (!$method->isPure()->maybe()) {
			return null;
		}
		if (!$method->hasSideEffects()->maybe()) {
			return null;
		}

		if (count($node->getImpurePoints()) !== 0) {
			return null;
		}

		if (count($node->getStatementResult()->getThrowPoints()) !== 0) {
			return null;
		}

		$variant = ParametersAcceptorSelector::selectSingle($method->getVariants());
		foreach ($variant->getParameters() as $parameter) {
			if (!$parameter->passedByReference()->createsNewVariable()) {
				continue;
			}

			return null;
		}

		if (count($method->getAsserts()->getAll()) !== 0) {
			return null;
		}

		$declaringClass = $method->getDeclaringClass();
		if (
			$declaringClass->hasConstructor()
			&& $declaringClass->getConstructor()->getName() === $method->getName()
		) {
			return null;
		}

		return [$method->getDeclaringClass()->getName(), $method->getName(), $method->getDeclaringClass()->getDisplayName()];
	}

}
