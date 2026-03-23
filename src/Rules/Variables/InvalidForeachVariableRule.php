<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function is_string;

/**
 * @implements Rule<Node\Stmt\Foreach_>
 */
#[RegisteredRule(level: 0)]
final class InvalidForeachVariableRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Foreach_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];

		$valueName = $this->getVariableName($node->valueVar);
		if ($valueName === 'this') {
			$errors[] = RuleErrorBuilder::message('Cannot re-assign $this.')
				->line($node->valueVar->getStartLine())
				->identifier('foreach.thisValue')
				->nonIgnorable()
				->build();
		}

		if ($node->keyVar !== null) {
			$keyName = $this->getVariableName($node->keyVar);
			if ($keyName === 'this') {
				$errors[] = RuleErrorBuilder::message('Cannot re-assign $this.')
					->line($node->keyVar->getStartLine())
					->identifier('foreach.thisKey')
					->nonIgnorable()
					->build();
			}
		}

		return $errors;
	}

	private function getVariableName(Node\Expr $expr): ?string
	{
		if (!$expr instanceof Variable) {
			return null;
		}

		if (!is_string($expr->name)) {
			return null;
		}

		return $expr->name;
	}

}
