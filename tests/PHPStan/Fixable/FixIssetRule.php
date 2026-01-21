<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function is_string;
use function sprintf;
use function substr;

/**
 * @implements Rule<Variable>
 */
class FixIssetRule implements Rule
{

	public function getNodeType(): string
	{
		return Variable::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!is_string($node->name)) {
			return [];
		}

		$lastCharacter = substr($node->name, -1);
		$newName = $node->name . $lastCharacter;

		return [
			RuleErrorBuilder::message(sprintf('Renaming $%s to $%s.', $node->name, $newName))
				->identifier('tests.renameVar')
				->fixNode($node, static function (Variable $v) use ($newName) {
					$v->name = $newName;

					return $v;
				})
				->build(),
		];
	}

}
