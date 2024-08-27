<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function array_slice;
use function count;

final class ArrayMapArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'arrayMapArgs';

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'array_map') {
				$args = $node->getArgs();
				if (isset($args[0])) {
					$slicedArgs = array_slice($args, 1);
					if (count($slicedArgs) > 0) {
						$args[0]->value->setAttribute(self::ATTRIBUTE_NAME, $slicedArgs);
					}
				}
			}
		}
		return null;
	}

}
