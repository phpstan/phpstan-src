<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class CurlSetOptArgVisitor extends NodeVisitorAbstract
{

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
			$functionName = $node->name->toLowerString();
			if ($functionName === 'curl_setopt') {
				$args = $node->getArgs();
				if (isset($args[0])) {
					$args[0]->setAttribute('isCurlSetOptArg', true);
				}
			}
		}
		return null;
	}

}
