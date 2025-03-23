<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_merge;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Variable>
 */
final class DefinedVariableRule implements Rule
{

	public function __construct(
		private bool $cliArgumentsVariablesRegistered,
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
		$errors = [];
		if (is_string($node->name)) {
			$variableNameScopes = [$node->name => $scope];
		} else {
			$nameType = $scope->getType($node->name);
			$variableNameScopes = [];
			foreach ($nameType->getConstantStrings() as $constantString) {
				$name = $constantString->getValue();
				$variableNameScopes[$name] = $scope->filterByTruthyValue(new Identical($node->name, new String_($name)));
			}
		}

		foreach ($variableNameScopes as $name => $variableScope) {
			$errors = array_merge($errors, $this->processSingleVariable($variableScope, $node, $name));
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
