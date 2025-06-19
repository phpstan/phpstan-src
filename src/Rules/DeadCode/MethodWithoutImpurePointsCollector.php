<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\Node\MethodReturnStatementsNode;
use function count;

/**
 * @implements Collector<MethodReturnStatementsNode, array{class-string, string, string}>
 */
#[RegisteredCollector(level: 4)]
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

		foreach ($method->getParameters() as $parameter) {
			if (!$parameter->passedByReference()->createsNewVariable()) {
				continue;
			}

			return null;
		}

		if (count($method->getAsserts()->getAll()) !== 0) {
			return null;
		}

		if ($method->isConstructor()) {
			return null;
		}

		return [$method->getDeclaringClass()->getName(), $method->getName(), $method->getDeclaringClass()->getDisplayName()];
	}

}
