<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class ImmediatelyInvokedClosureVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isImmediatelyInvokedClosure';

	private bool $inFuncCall = false;

	public function beforeTraverse(array $nodes): ?array
	{
		$this->inFuncCall = false;
		return null;
	}

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall) {
			$this->inFuncCall = true;
		}

		if (
			$this->inFuncCall
			&& $node instanceof Node\Expr\Closure
		) {
			$node->setAttribute(self::ATTRIBUTE_NAME, true);
		}

		return null;
	}

	public function leaveNode(Node $node): ?Node
	{
		if ($node instanceof Node\Expr\FuncCall) {
			$this->inFuncCall = false;
		}

		return null;
	}

}
