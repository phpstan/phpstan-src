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
 * @implements Rule<Node\FunctionLike>
 */
#[RegisteredRule(level: 0)]
final class InvalidParameterNameRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\FunctionLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];

		foreach ($node->getParams() as $param) {
			if (!$param->var instanceof Node\Expr\Variable) {
				continue;
			}

			if (!is_string($param->var->name)) {
				continue;
			}

			$variableName = $param->var->name;

			if (in_array($variableName, Scope::SUPERGLOBAL_VARIABLES, true)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Cannot re-assign auto-global variable $%s.', $variableName))
					->line($param->getStartLine())
					->identifier('parameter.invalidExpr')
					->nonIgnorable()
					->build();
			} elseif ($variableName === 'this') {
				$errors[] = RuleErrorBuilder::message('Cannot use $this as parameter.')
					->line($param->getStartLine())
					->identifier('parameter.invalidExpr')
					->nonIgnorable()
					->build();
			}

		}

		return $errors;
	}

}
