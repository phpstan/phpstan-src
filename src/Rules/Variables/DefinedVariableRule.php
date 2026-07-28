<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\VariableNameResolver;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_merge;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Variable>
 */
#[RegisteredRule(level: 0)]
final class DefinedVariableRule implements Rule
{

	public function __construct(
		#[AutowiredParameter]
		private bool $cliArgumentsVariablesRegistered,
		#[AutowiredParameter]
		private bool $checkMaybeUndefinedVariables,
	)
	{
	}

	public function getNodeType(): string
	{
		return Variable::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$namesWithScopes = VariableNameResolver::resolveNamesWithScopes($scope, $node);
		if ($namesWithScopes === null) {
			return [];
		}

		$errors = [];
		foreach ($namesWithScopes as [$name, $variableScope]) {
			$errors = array_merge($errors, $this->processSingleVariable(
				$variableScope,
				$node,
				$name,
			));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleVariable(Scope $scope, Variable $node, string $variableName): array
	{
		if ($this->cliArgumentsVariablesRegistered && in_array($variableName, [
			'argc',
			'argv',
		], true)) {
			$isInMain = !$scope->isInClass() && !$scope->isInAnonymousFunction() && $scope->getFunction() === null;
			if ($isInMain) {
				return [];
			}
		}

		if ($scope->isInExpressionAssign($node) || $scope->isUndefinedExpressionAllowed($node)) {
			return [];
		}

		if ($scope->hasVariableType($variableName)->no()) {
			return [
				RuleErrorBuilder::message(sprintf('Undefined variable: $%s', $variableName))
					->identifier('variable.undefined')
					->build(),
			];
		} elseif (
			$this->checkMaybeUndefinedVariables
			&& !$scope->hasVariableType($variableName)->yes()
		) {
			return [
				RuleErrorBuilder::message(sprintf('Variable $%s might not be defined.', $variableName))
					->identifier('variable.undefined')
					->build(),
			];
		}

		return [];
	}

}
