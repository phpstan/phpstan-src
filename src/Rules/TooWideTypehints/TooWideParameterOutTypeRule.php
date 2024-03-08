<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<ExecutionEndNode>
 */
class TooWideParameterOutTypeRule implements Rule
{

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
		$isParamOutType = true;
		$outType = $parameter->getOutType();
		if ($outType === null) {
			$isParamOutType = false;
			$outType = $parameter->getType();
		}

		if (!$outType instanceof UnionType) {
			return [];
		}

		$variableExpr = new Node\Expr\Variable($parameter->getName());
		$variableType = $scope->getType($variableExpr);

		if ($inFunction instanceof ExtendedMethodReflection) {
			$functionDescription = sprintf('Method %s::%s()', $inFunction->getDeclaringClass()->getDisplayName(), $inFunction->getName());
		} else {
			$functionDescription = sprintf('Function %s()', $inFunction->getName());
		}

		$messages = [];
		foreach ($outType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($variableType)->no()) {
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'%s never assigns %s to &$%s so it can be removed from the %s.',
				$functionDescription,
				$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
				$parameter->getName(),
				$isParamOutType ? '@param-out type' : 'by-ref type',
			));
			if (!$isParamOutType) {
				$errorBuilder->tip('You can narrow the parameter out type with @param-out PHPDoc tag.');
			}

			$messages[] = $errorBuilder->build();
		}

		return $messages;
	}

}
