<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\Node\MethodReturnStatementsNode;
use function count;

/**
 * @implements Collector<MethodReturnStatementsNode, array{string, list<string>}>
 */
#[RegisteredCollector(level: 4)]
final class ConstructorWithoutImpurePointsCollector implements Collector
{

	public function __construct(private PossiblyPureCallTransitivePurityResolver $purityResolver)
	{
	}

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		$method = $node->getMethodReflection();
		if (!$method->isConstructor()) {
			return null;
		}

		if (!$method->isPure()->maybe()) {
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

		$throwType = $method->getThrowType();
		if ($throwType !== null && !$throwType->isVoid()->yes()) {
			return null;
		}

		$dependencies = $this->purityResolver->resolveDependencies(
			$node->getImpurePoints(),
			$node->getStatementResult()->getThrowPoints(),
		);
		if ($dependencies === null) {
			return null;
		}

		return [$method->getDeclaringClass()->getName(), $dependencies];
	}

}
