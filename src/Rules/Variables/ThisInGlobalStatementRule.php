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
 * @implements Rule<Node\Stmt\Global_>
 */
#[RegisteredRule(level: 0)]
final class ThisInGlobalStatementRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Global_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->vars as $var) {
			if (!$var instanceof Variable) {
				continue;
			}
			if (!is_string($var->name)) {
				continue;
			}
			if ($var->name !== 'this') {
				continue;
			}

			$errors[] = RuleErrorBuilder::message('Cannot use $this as global variable.')
				->identifier('global.this')
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
