<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function is_string;
use function sprintf;

/**
 * @implements Rule<VariableAssignNode>
 */
class ParameterOutAssignedTypeRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
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

		$variant = ParametersAcceptorSelector::selectSingle($inFunction->getVariants());
		$parameters = $variant->getParameters();
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

		if ($foundParameter->getOutType() === null) {
			return [];
		}

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->getAssignedExpr(),
			'',
			static fn (Type $type): bool => $foundParameter->getOutType()->isSuperTypeOf($type)->yes(),
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		$assignedExprType = $scope->getType($node->getAssignedExpr());
		if ($foundParameter->getOutType()->isSuperTypeOf($assignedExprType)->yes()) {
			return [];
		}

		if ($inFunction instanceof ExtendedMethodReflection) {
			$functionDescription = sprintf('method %s::%s()', $inFunction->getDeclaringClass()->getDisplayName(), $inFunction->getName());
		} else {
			$functionDescription = sprintf('function %s()', $inFunction->getName());
		}

		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($foundParameter->getOutType(), $assignedExprType);

		return [
			RuleErrorBuilder::message(sprintf(
				'Parameter &$%s out type of %s expects %s, %s given.',
				$foundParameter->getName(),
				$functionDescription,
				$foundParameter->getOutType()->describe($verbosityLevel),
				$assignedExprType->describe($verbosityLevel),
			))->build(),
		];
	}

}
