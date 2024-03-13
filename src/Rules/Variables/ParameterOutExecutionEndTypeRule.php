<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\Expr\ParameterVariableOriginalValueExpr;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<ExecutionEndNode>
 */
class ParameterOutExecutionEndTypeRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return ExecutionEndNode::class;
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

		$variant = ParametersAcceptorSelector::selectSingle($inFunction->getVariants());
		$parameters = $variant->getParameters();
		$errors = [];
		foreach ($parameters as $parameter) {
			if (!$parameter->passedByReference()->createsNewVariable()) {
				continue;
			}

			foreach ($this->processSingleParameter($scope, $inFunction, $parameter) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * @return array<RuleError>
	 */
	private function processSingleParameter(
		Scope $scope,
		FunctionReflection|ExtendedMethodReflection $inFunction,
		ParameterReflectionWithPhpDocs $parameter,
	): array
	{
		$outType = $parameter->getOutType();
		if ($outType === null) {
			return [];
		}

		if ($scope->hasExpressionType(new ParameterVariableOriginalValueExpr($parameter->getName()))->no()) {
			return [];
		}

		$outType = TypeUtils::resolveLateResolvableTypes($outType);

		$variableExpr = new Node\Expr\Variable($parameter->getName());
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$variableExpr,
			'',
			static fn (Type $type): bool => $outType->isSuperTypeOf($type)->yes(),
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		$assignedExprType = $scope->getType($variableExpr);
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
			'Parameter &$%s @param-out type of %s expects %s, %s given.',
			$parameter->getName(),
			$functionDescription,
			$outType->describe($verbosityLevel),
			$assignedExprType->describe($verbosityLevel),
		))->identifier(sprintf('paramOut.type'));

		return [
			$errorBuilder->build(),
		];
	}

}
