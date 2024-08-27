<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use function count;
use function strtolower;

/**
 * @implements Collector<MethodReturnStatementsNode, string>
 */
final class ConstructorWithoutImpurePointsCollector implements Collector
{

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		$method = $node->getMethodReflection();
		if (strtolower($method->getName()) !== '__construct') {
			return null;
		}

		if (!$method->isPure()->maybe()) {
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

		return $method->getDeclaringClass()->getName();
	}

}
