<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

final class TooWideParameterOutTypeCheck
{

	/**
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param list<ReturnStatement> $returnStatements
	 * @param ParameterReflectionWithPhpDocs[] $parameters
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		array $executionEnds,
		array $returnStatements,
		array $parameters,
		string $functionDescription,
	): array
	{
		$finalScope = null;
		foreach ($executionEnds as $executionEnd) {
			$endScope = $executionEnd->getStatementResult()->getScope();
			if ($finalScope === null) {
				$finalScope = $endScope;
				continue;
			}

			$finalScope = $finalScope->mergeWith($endScope);
		}

		foreach ($returnStatements as $statement) {
			if ($finalScope === null) {
				$finalScope = $statement->getScope();
				continue;
			}

			$finalScope = $finalScope->mergeWith($statement->getScope());
		}

		if ($finalScope === null) {
			return [];
		}

		$errors = [];
		foreach ($parameters as $parameter) {
			if (!$parameter->passedByReference()->createsNewVariable()) {
				continue;
			}

			foreach ($this->processSingleParameter($finalScope, $functionDescription, $parameter) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleParameter(
		Scope $scope,
		string $functionDescription,
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

		$variableExpr = new Variable($parameter->getName());
		$variableType = $scope->getType($variableExpr);

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
			))->identifier(sprintf('%s.unusedType', $isParamOutType ? 'paramOut' : 'parameterByRef'));
			if (!$isParamOutType) {
				$errorBuilder->tip('You can narrow the parameter out type with @param-out PHPDoc tag.');
			}

			$messages[] = $errorBuilder->build();
		}

		return $messages;
	}

}
