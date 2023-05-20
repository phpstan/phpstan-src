<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Variable>
 */
class DefinedVariableRule implements Rule
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
		if (!is_string($node->name)) {
			return [];
		}

		if ($this->cliArgumentsVariablesRegistered && in_array($node->name, [
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

		if ($scope->hasVariableType($node->name)->no()) {
			return [
				RuleErrorBuilder::message(sprintf('Undefined variable: $%s', $node->name))
					->identifier('variable.undefined')
					->build(),
			];
		} elseif (
			$this->checkMaybeUndefinedVariables
			&& !$scope->hasVariableType($node->name)->yes()
		) {
			return [
				RuleErrorBuilder::message(sprintf('Variable $%s might not be defined.', $node->name))
					->identifier('variable.undefined')
					->build(),
			];
		}

		return [];
	}

}
