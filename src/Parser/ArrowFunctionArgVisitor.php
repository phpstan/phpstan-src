<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\DependencyInjection\AutowiredService;
use function count;

#[AutowiredService]
final class ArrowFunctionArgVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'arrowFunctionCallArgs';

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof Node\Expr\FuncCall) {
			return null;
		}

		if ($node->isFirstClassCallable()) {
			return null;
		}

		if ($node->name instanceof Node\Expr\Assign && $node->name->expr instanceof Node\Expr\ArrowFunction) {
			$arrow = $node->name->expr;
		} elseif ($node->name instanceof Node\Expr\ArrowFunction) {
			$arrow = $node->name;
		} else {
			return null;
		}

		$args = $node->getArgs();

		if (count($args) > 0) {
			$arrow->setAttribute(self::ATTRIBUTE_NAME, $args);
		}

		return null;
	}

}
