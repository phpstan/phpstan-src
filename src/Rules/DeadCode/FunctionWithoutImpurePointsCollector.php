<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\Node\FunctionReturnStatementsNode;
use function count;

/**
 * @implements Collector<FunctionReturnStatementsNode, array{string, list<string>}>
 */
#[RegisteredCollector(level: 4)]
final class FunctionWithoutImpurePointsCollector implements Collector
{

	public function __construct(private PossiblyPureCallTransitivePurityResolver $purityResolver)
	{
	}

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope)
	{
		$function = $node->getFunctionReflection();
		if (!$function->isPure()->maybe()) {
			return null;
		}
		if (!$function->hasSideEffects()->maybe()) {
			return null;
		}

		foreach ($function->getParameters() as $parameter) {
			if (!$parameter->passedByReference()->createsNewVariable()) {
				continue;
			}

			return null;
		}

		if (count($function->getAsserts()->getAll()) !== 0) {
			return null;
		}

		$throwType = $function->getThrowType();
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

		return [$function->getName(), $dependencies];
	}

}
