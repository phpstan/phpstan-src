<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\ExecutionEndNode;
use PHPStan\Node\ReturnStatement;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Rules\IdentifierRuleError;
use function lcfirst;
use function sprintf;

#[AutowiredService]
final class TooWideParameterOutTypeCheck
{

	public function __construct(
		private TooWideTypeCheck $tooWideTypeCheck,
	)
	{
	}

	/**
	 * @param list<ExecutionEndNode> $executionEnds
	 * @param list<ReturnStatement> $returnStatements
	 * @param ExtendedParameterReflection[] $parameters
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		int $startLine,
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

			foreach ($this->processSingleParameter($startLine, $finalScope, $functionDescription, $parameter) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleParameter(
		int $startLine,
		Scope $scope,
		string $functionDescription,
		ExtendedParameterReflection $parameter,
	): array
	{
		$isParamOutType = true;
		$outType = $parameter->getOutType();
		if ($outType === null) {
			$isParamOutType = false;
			$outType = $parameter->getType();
		}

		$variableExpr = new Variable($parameter->getName());
		$variableType = $scope->getType($variableExpr);

		return $this->tooWideTypeCheck->checkParameterOutType(
			$outType,
			$variableType,
			sprintf(
				'%s never assigns %%s to &$%s so it can be removed from the %s.',
				$functionDescription,
				$parameter->getName(),
				$isParamOutType ? '@param-out type' : 'by-ref type',
			),
			sprintf(
				'%s never assigns %%s to &$%s so the %s can be changed to %%s.',
				$functionDescription,
				$parameter->getName(),
				$isParamOutType ? '@param-out type' : 'by-ref type',
			),
			sprintf(
				'%s %%s of %s can be narrowed to %%s.',
				$isParamOutType ? 'PHPDoc tag @param-out type' : 'By-ref type',
				lcfirst($functionDescription),
			),
			$scope,
			$startLine,
			$isParamOutType ? 'paramOut' : 'parameterByRef',
			$isParamOutType ? null : 'You can narrow the parameter out type with @param-out PHPDoc tag.',
		);
	}

}
