<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function is_string;
use function sprintf;

/**
 * @implements Rule<Node\FunctionLike>
 */
class RedefinedParametersRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\FunctionLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$params = $node->getParams();

		if (count($params) <= 1) {
			return [];
		}

		$vars = [];
		$errors = [];

		foreach ($params as $param) {
			if (!$param->var instanceof Node\Expr\Variable) {
				continue;
			}

			if (!is_string($param->var->name)) {
				continue;
			}

			$var = $param->var->name;

			if (!isset($vars[$var])) {
				$vars[$var] = true;
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf('Redefinition of parameter $%s.', $var))
				->identifier('parameter.duplicate')
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
