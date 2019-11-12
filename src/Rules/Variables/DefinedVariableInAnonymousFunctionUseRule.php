<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\ClosureUse;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\ClosureUse>
 */
class DefinedVariableInAnonymousFunctionUseRule implements \PHPStan\Rules\Rule
{

	/** @var bool */
	private $checkMaybeUndefinedVariables;

	public function __construct(
		bool $checkMaybeUndefinedVariables
	)
	{
		$this->checkMaybeUndefinedVariables = $checkMaybeUndefinedVariables;
	}

	public function getNodeType(): string
	{
		return ClosureUse::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->byRef || !is_string($node->var->name)) {
			return [];
		}

		if ($scope->hasVariableType($node->var->name)->no()) {
			return [
				RuleErrorBuilder::message(sprintf('Undefined variable: $%s', $node->var->name))->build(),
			];
		} elseif (
			$this->checkMaybeUndefinedVariables
			&& !$scope->hasVariableType($node->var->name)->yes()
		) {
			return [
				RuleErrorBuilder::message(sprintf('Variable $%s might not be defined.', $node->var->name))->build(),
			];
		}

		return [];
	}

}
