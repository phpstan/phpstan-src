<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\TypeUtils;
use function is_string;

/**
 * @implements Rule<VariableAssignNode>
 */
#[RegisteredRule(level: 3)]
final class ParameterOutAssignedTypeRule implements Rule
{

	public function __construct(
		private ParameterOutTypeCheck $parameterOutTypeCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return VariableAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$inFunction = $scope->getFunction();
		if ($inFunction === null) {
			return [];
		}

		if ($scope->isInAnonymousFunction()) {
			return [];
		}

		$variable = $node->getVariable();
		if (!is_string($variable->name)) {
			return [];
		}

		$parameters = $inFunction->getParameters();
		$foundParameter = null;
		foreach ($parameters as $parameter) {
			if (!$parameter->passedByReference()->createsNewVariable()) {
				continue;
			}
			if ($parameter->getName() !== $variable->name) {
				continue;
			}

			$foundParameter = $parameter;
			break;
		}

		if ($foundParameter === null) {
			return [];
		}

		$isParamOutType = true;
		$outType = $foundParameter->getOutType();
		if ($outType === null) {
			$isParamOutType = false;
			$outType = $foundParameter->getType();
		}

		$outType = TypeUtils::resolveLateResolvableTypes($outType);

		return $this->parameterOutTypeCheck->check(
			$scope,
			$inFunction,
			$foundParameter,
			$node->getAssignedExpr(),
			$outType,
			$isParamOutType,
		);
	}

}
