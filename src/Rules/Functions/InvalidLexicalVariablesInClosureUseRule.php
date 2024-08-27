<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_filter;
use function array_map;
use function in_array;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Closure>
 */
final class InvalidLexicalVariablesInClosureUseRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\Closure::class;
	}

	/**
	 * @param Node\Expr\Closure $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		$params = array_filter(array_map(
			static function (Node\Param $param) {
				if (!$param->var instanceof Node\Expr\Variable) {
					return false;
				}

				if (!is_string($param->var->name)) {
					return false;
				}

				return $param->var->name;
			},
			$node->getParams(),
		), static fn ($name) => $name !== false);

		foreach ($node->uses as $use) {
			if (!is_string($use->var->name)) {
				continue;
			}

			$var = $use->var->name;

			if ($var === 'this') {
				$errors[] = RuleErrorBuilder::message('Cannot use $this as lexical variable.')
					->line($use->getStartLine())
					->identifier('closure.useThis')
					->nonIgnorable()
					->build();
				continue;
			}

			if (in_array($var, Scope::SUPERGLOBAL_VARIABLES, true)) {
				$errors[] = RuleErrorBuilder::message(sprintf('Cannot use superglobal variable $%s as lexical variable.', $var))
					->line($use->getStartLine())
					->identifier('closure.useSuperGlobal')
					->nonIgnorable()
					->build();
				continue;
			}

			if (!in_array($var, $params, true)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Cannot use lexical variable $%s since a parameter with the same name already exists.', $var))
				->line($use->getStartLine())
				->identifier('closure.useDuplicate')
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
