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

		$isParamOutType = true;
		$outType = $foundParameter->getOutType();
		if ($outType === null) {
			$isParamOutType = false;
			$outType = $foundParameter->getType();
		}

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->getAssignedExpr(),
			'',
			static fn (Type $type): bool => $outType->isSuperTypeOf($type)->yes(),
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		$assignedExprType = $scope->getType($node->getAssignedExpr());
		if ($outType->isSuperTypeOf($assignedExprType)->yes()) {
			return [];
		}

		if ($inFunction instanceof ExtendedMethodReflection) {
			$functionDescription = sprintf('method %s::%s()', $inFunction->getDeclaringClass()->getDisplayName(), $inFunction->getName());
		} else {
			$functionDescription = sprintf('function %s()', $inFunction->getName());
		}

		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($outType, $assignedExprType);
		$errorBuilder = RuleErrorBuilder::message(sprintf(
			'Parameter &$%s %s of %s expects %s, %s given.',
			$foundParameter->getName(),
			$isParamOutType ? '@param-out type' : 'by-ref type',
			$functionDescription,
			$outType->describe($verbosityLevel),
			$assignedExprType->describe($verbosityLevel),
		));

		if (!$isParamOutType) {
			$errorBuilder->tip('You can change the parameter out type with @param-out PHPDoc tag.');
		}

		return [
			$errorBuilder->build(),
		];
	}

}
