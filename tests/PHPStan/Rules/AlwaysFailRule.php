<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class AlwaysFailRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		if ($node->name->toLowerString() !== 'fail') {
			return [];
		}

		return ['Fail.'];
	}

}
