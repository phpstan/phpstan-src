<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use function count;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class AlwaysFailRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	/**
	 * @param Node\Expr\FuncCall $node
	 * @return string[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		if ($node->name->toLowerString() !== 'fail') {
			return [];
		}

		if (count($node->getArgs()) === 1 && $node->getArgs()[0]->value instanceof Node\Scalar\String_) {
			return [$node->getArgs()[0]->value->value];
		}

		return ['Fail.'];
	}

}
