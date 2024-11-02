<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function in_array;

final class ArrayFindArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isArrayFindArg';

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
			$functionName = $node->name->toLowerString();
			if (in_array($functionName, ['array_find', 'array_find_key'], true)) {
				$args = $node->getRawArgs();
				if (isset($args[0])) {
					$args[0]->setAttribute(self::ATTRIBUTE_NAME, true);
				}
			}
		}
		return null;
	}

}
