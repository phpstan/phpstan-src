<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Variable>
 */
class DefinedVariableRule implements \PHPStan\Rules\Rule
{

	/** @var bool */
	private $cliArgumentsVariablesRegistered;

	/** @var bool */
	private $checkMaybeUndefinedVariables;

	public function __construct(
		bool $cliArgumentsVariablesRegistered,
		bool $checkMaybeUndefinedVariables
	)
	{
		$this->cliArgumentsVariablesRegistered = $cliArgumentsVariablesRegistered;
		$this->checkMaybeUndefinedVariables = $checkMaybeUndefinedVariables;
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

		if ($scope->isInExpressionAssign($node)) {
			return [];
		}

		if ($scope->hasVariableType($node->name)->no()) {
			return [
				RuleErrorBuilder::message(sprintf('Undefined variable: $%s', $node->name))->build(),
			];
		} elseif (
			$this->checkMaybeUndefinedVariables
			&& !$scope->hasVariableType($node->name)->yes()
		) {
			return [
				RuleErrorBuilder::message(sprintf('Variable $%s might not be defined.', $node->name))->build(),
			];
		}

		return [];
	}

}
