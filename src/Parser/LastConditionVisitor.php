<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function count;

class LastConditionVisitor extends NodeVisitorAbstract
{

	public const ATTRIBUTE_NAME = 'isLastCondition';
	public const ATTRIBUTE_IS_MATCH_NAME = 'isMatch';

	public function enterNode(Node $node): ?Node
	{
		if ($node instanceof Node\Stmt\If_ && $node->elseifs !== []) {
			$lastElseIf = count($node->elseifs) - 1;

			foreach ($node->elseifs as $i => $elseif) {
				$isLast = $i === $lastElseIf && $node->else === null;
				$elseif->cond->setAttribute(self::ATTRIBUTE_NAME, $isLast);
			}
		}

		if ($node instanceof Node\Expr\Match_ && $node->arms !== []) {
			$lastArm = count($node->arms) - 1;

			foreach ($node->arms as $i => $arm) {
				if ($arm->conds === null || $arm->conds === []) {
					continue;
				}

				$isLast = $i === $lastArm;
				$index = count($arm->conds) - 1;
				$arm->conds[$index]->setAttribute(self::ATTRIBUTE_NAME, $isLast);
				$arm->conds[$index]->setAttribute(self::ATTRIBUTE_IS_MATCH_NAME, true);
			}
		}

		return null;
	}

}
