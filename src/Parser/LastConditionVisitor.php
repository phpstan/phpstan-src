<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

class LastConditionVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isLastCondition';

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\If_ && $node->elseifs !== [] && $node->else === null) {
			$lastElseIf = count($node->elseifs) - 1;

			$node->elseifs[$lastElseIf]->cond->setAttribute(self::ATTRIBUTE_NAME, true);
		}

		if ($node instanceof Node\Expr\Match_ && $node->arms !== []) {
			$lastArm = count($node->arms) - 1;

			if ($node->arms[$lastArm]->conds !== null && $node->arms[$lastArm]->conds !== []) {
				$node->arms[$lastArm]->conds[0]->setAttribute(self::ATTRIBUTE_NAME, true);
			}
		}

		return null;
	}

}
