<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<Node\Expr\Variable>
 */
class Bug13813Rule implements Rule
{

	public function getNodeType(): string
	{
		return Variable::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		for ($i = 0; $i < 100; $i++) {
			// @phpstan-ignore variable.undefined
			echo $x; // force emit a PHP warning at runtime
		}

		return [];
	}

}
