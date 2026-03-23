<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Param>
 */
#[RegisteredRule(level: 0)]
final class InvalidParameterNameRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Param::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->var instanceof Node\Expr\Variable) {
			return [];
		}

		if (!is_string($node->var->name)) {
			return [];
		}

		$variableName = $node->var->name;

		if (in_array($variableName, Scope::SUPERGLOBAL_VARIABLES, true)) {
			return [
				RuleErrorBuilder::message(sprintf('Superglobal variable $%s cannot be used as a parameter.', $variableName))
					->identifier('parameter.superglobal')
					->nonIgnorable()
					->build(),
			];
		}

		if ($variableName === 'this') {
			return [
				RuleErrorBuilder::message('Cannot use $this as parameter.')
					->identifier('parameter.this')
					->nonIgnorable()
					->build(),
			];
		}

		return [];
	}

}
