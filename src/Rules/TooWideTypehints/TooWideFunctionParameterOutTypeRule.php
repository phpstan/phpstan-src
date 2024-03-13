<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
class TooWideFunctionParameterOutTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$inFunction = $node->getFunctionReflection();
		$finalScope = null;
		foreach ($node->getExecutionEnds() as $executionEnd) {
			$endScope = $executionEnd->getStatementResult()->getScope();
			if ($finalScope === null) {
				$finalScope = $endScope;
				continue;
			}

			$finalScope = $finalScope->mergeWith($endScope);
		}

		foreach ($node->getReturnStatements() as $statement) {
			if ($finalScope === null) {
				$finalScope = $statement->getScope();
				continue;
			}

			$finalScope = $finalScope->mergeWith($statement->getScope());
		}

		if ($finalScope === null) {
			return [];
		}

		$variant = ParametersAcceptorSelector::selectSingle($inFunction->getVariants());
		$parameters = $variant->getParameters();
		$errors = [];
		foreach ($parameters as $parameter) {
			if (!$parameter->passedByReference()->createsNewVariable()) {
				continue;
			}

			foreach ($this->processSingleParameter($finalScope, $inFunction, $parameter) as $error) {
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
		FunctionReflection $inFunction,
		ParameterReflectionWithPhpDocs $parameter,
	): array
	{
		$isParamOutType = true;
		$outType = $parameter->getOutType();
		if ($outType === null) {
			$isParamOutType = false;
			$outType = $parameter->getType();
		}

		$outType = TypeUtils::resolveLateResolvableTypes($outType);
		if (!$outType instanceof UnionType) {
			return [];
		}

		$variableExpr = new Node\Expr\Variable($parameter->getName());
		$variableType = $scope->getType($variableExpr);

		$messages = [];
		foreach ($outType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($variableType)->no()) {
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'Function %s() never assigns %s to &$%s so it can be removed from the %s.',
				$inFunction->getName(),
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
