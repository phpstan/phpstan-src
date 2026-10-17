<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function is_string;

/**
 * @implements Rule<Node\Stmt\Static_>
 */
#[RegisteredRule(level: 0)]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/ThisInStaticStatementRule.cpp')]
final class ThisInStaticStatementRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Static_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($node->vars as $var) {
			if (!is_string($var->var->name)) {
				continue;
			}
			if ($var->var->name !== 'this') {
				continue;
			}

			$errors[] = RuleErrorBuilder::message('Cannot use $this as static variable.')
				->identifier('static.this')
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
