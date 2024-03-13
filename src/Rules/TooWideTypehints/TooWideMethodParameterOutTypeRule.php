<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Reflection\ExtendedMethodReflection;
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
 * @implements Rule<MethodReturnStatementsNode>
 */
class TooWideMethodParameterOutTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$inMethod = $node->getMethodReflection();
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

		$variant = ParametersAcceptorSelector::selectSingle($inMethod->getVariants());
		$parameters = $variant->getParameters();
		$errors = [];
		foreach ($parameters as $parameter) {
			if (!$parameter->passedByReference()->createsNewVariable()) {
				continue;
			}

			foreach ($this->processSingleParameter($finalScope, $inMethod, $parameter) as $error) {
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
		ExtendedMethodReflection $inMethod,
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
				'Method %s::%s() never assigns %s to &$%s so it can be removed from the %s.',
				$inMethod->getDeclaringClass()->getDisplayName(),
				$inMethod->getName(),
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
