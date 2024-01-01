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

			$elseIsMissingOrThrowing = $node->else === null
				|| (
					count($node->else->stmts) === 1
					&& $node->else->stmts[0] instanceof Node\Stmt\Expression
					&& $node->else->stmts[0]->expr instanceof Node\Expr\Throw_
				);

			foreach ($node->elseifs as $i => $elseif) {
				$isLast = $i === $lastElseIf && $elseIsMissingOrThrowing;
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

		if (
			$node instanceof Node\Stmt\Function_
			|| $node instanceof Node\Stmt\ClassMethod
			|| $node instanceof Node\Stmt\If_
			|| $node instanceof Node\Stmt\ElseIf_
			|| $node instanceof Node\Stmt\Else_
			|| $node instanceof Node\Stmt\Case_
			|| $node instanceof Node\Stmt\Catch_
			|| $node instanceof Node\Stmt\Do_
			|| $node instanceof Node\Stmt\Finally_
			|| $node instanceof Node\Stmt\For_
			|| $node instanceof Node\Stmt\Foreach_
			|| $node instanceof Node\Stmt\Namespace_
			|| $node instanceof Node\Stmt\TryCatch
			|| $node instanceof Node\Stmt\While_
		) {
			$statements = $node->stmts ?? [];
			$statementCount = count($statements);

			if ($statementCount < 2) {
				return null;
			}

			$lastStatement = $statements[$statementCount - 1];

			if (!$lastStatement instanceof Node\Stmt\Expression) {
				return null;
			}

			if (!$lastStatement->expr instanceof Node\Expr\Throw_) {
				return null;
			}

			if (!$statements[$statementCount - 2] instanceof Node\Stmt\If_ || $statements[$statementCount - 2]->else !== null) {
				return null;
			}

			$if = $statements[$statementCount - 2];
			$cond = count($if->elseifs) > 0 ? $if->elseifs[count($if->elseifs) - 1]->cond : $if->cond;
			$cond->setAttribute(self::ATTRIBUTE_NAME, true);
		}

		return null;
	}

}
