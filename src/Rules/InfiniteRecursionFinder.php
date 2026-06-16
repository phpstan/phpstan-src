<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\BitwiseNot;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Cast;
use PhpParser\Node\Expr\Clone_;
use PhpParser\Node\Expr\ErrorSuppress;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\Print_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PhpParser\Node\Stmt;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\DependencyInjection\AutowiredService;
use function array_merge;

/**
 * Detects functions and methods that unconditionally call themselves on every
 * code path, which always leads to infinite recursion.
 *
 * The analysis is intentionally conservative: it walks the body's statements in
 * order and only considers self-calls that are guaranteed to be evaluated. As
 * soon as it encounters any branching construct (if/switch/loop/try/...) or a
 * statement that stops normal control flow without recursing first, it gives up
 * — a base case might be hiding there. This mirrors the issue's observation that
 * such mistakes have "no branching for the return statement".
 */
#[AutowiredService]
final class InfiniteRecursionFinder
{

	private const STOP = 'stop';

	private const CONTINUE = 'continue';

	/**
	 * Returns the self-call that is guaranteed to be reached on every code path,
	 * or null when no unconditional self-recursion was found.
	 *
	 * @param Stmt[] $stmts
	 * @param callable(Expr): bool $isSelfCall
	 */
	public function find(array $stmts, callable $isSelfCall): ?Expr
	{
		$result = $this->processStmts($stmts, $isSelfCall);

		return $result instanceof Expr ? $result : null;
	}

	/**
	 * @param Stmt[] $stmts
	 * @param callable(Expr): bool $isSelfCall
	 * @return Expr|self::STOP|self::CONTINUE
	 */
	private function processStmts(array $stmts, callable $isSelfCall)
	{
		foreach ($stmts as $stmt) {
			$result = $this->processStmt($stmt, $isSelfCall);
			if ($result instanceof Expr) {
				return $result;
			}
			if ($result === self::STOP) {
				return self::STOP;
			}
		}

		return self::CONTINUE;
	}

	/**
	 * @param callable(Expr): bool $isSelfCall
	 * @return Expr|self::STOP|self::CONTINUE
	 */
	private function processStmt(Stmt $stmt, callable $isSelfCall)
	{
		if ($stmt instanceof Stmt\Expression) {
			$call = $this->findInExpr($stmt->expr, $isSelfCall);
			if ($call !== null) {
				return $call;
			}

			return $this->alwaysStops($stmt->expr) ? self::STOP : self::CONTINUE;
		}

		if ($stmt instanceof Stmt\Return_) {
			if ($stmt->expr !== null) {
				$call = $this->findInExpr($stmt->expr, $isSelfCall);
				if ($call !== null) {
					return $call;
				}
			}

			return self::STOP;
		}

		if ($stmt instanceof Stmt\Echo_) {
			foreach ($stmt->exprs as $expr) {
				$call = $this->findInExpr($expr, $isSelfCall);
				if ($call !== null) {
					return $call;
				}
			}

			return self::CONTINUE;
		}

		if ($stmt instanceof Stmt\Block) {
			return $this->processStmts($stmt->stmts, $isSelfCall);
		}

		if (
			$stmt instanceof Stmt\Nop
			|| $stmt instanceof Stmt\Static_
			|| $stmt instanceof Stmt\Global_
			|| $stmt instanceof Stmt\InlineHTML
		) {
			return self::CONTINUE;
		}

		// Any other statement is either branching control flow or stops the
		// normal flow before a guaranteed self-call. Either way, bail out.
		return self::STOP;
	}

	/**
	 * @param callable(Expr): bool $isSelfCall
	 */
	private function findInExpr(Expr $expr, callable $isSelfCall): ?Expr
	{
		if ($isSelfCall($expr)) {
			return $expr;
		}

		foreach ($this->getUnconditionalSubExprs($expr) as $subExpr) {
			$found = $this->findInExpr($subExpr, $isSelfCall);
			if ($found !== null) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Sub-expressions that are guaranteed to be evaluated when $expr is
	 * evaluated, regardless of any short-circuiting or branching.
	 *
	 * @return Expr[]
	 */
	private function getUnconditionalSubExprs(Expr $expr): array
	{
		if ($expr instanceof Assign || $expr instanceof AssignRef || $expr instanceof AssignOp) {
			return [$expr->var, $expr->expr];
		}

		if (
			$expr instanceof BooleanAnd
			|| $expr instanceof BooleanOr
			|| $expr instanceof LogicalAnd
			|| $expr instanceof LogicalOr
			|| $expr instanceof Coalesce
		) {
			return [$expr->left];
		}

		if ($expr instanceof BinaryOp) {
			return [$expr->left, $expr->right];
		}

		if ($expr instanceof Ternary) {
			return [$expr->cond];
		}

		if ($expr instanceof Match_) {
			return [$expr->cond];
		}

		if ($expr instanceof MethodCall) {
			$subExprs = [$expr->var];
			if ($expr->name instanceof Expr) {
				$subExprs[] = $expr->name;
			}

			return array_merge($subExprs, $this->getArgExprs($expr->args));
		}

		if ($expr instanceof NullsafeMethodCall) {
			return [$expr->var];
		}

		if ($expr instanceof StaticCall) {
			$subExprs = [];
			if ($expr->class instanceof Expr) {
				$subExprs[] = $expr->class;
			}
			if ($expr->name instanceof Expr) {
				$subExprs[] = $expr->name;
			}

			return array_merge($subExprs, $this->getArgExprs($expr->args));
		}

		if ($expr instanceof FuncCall) {
			$subExprs = [];
			if ($expr->name instanceof Expr) {
				$subExprs[] = $expr->name;
			}

			return array_merge($subExprs, $this->getArgExprs($expr->args));
		}

		if ($expr instanceof New_) {
			$subExprs = [];
			if ($expr->class instanceof Expr) {
				$subExprs[] = $expr->class;
			}

			return array_merge($subExprs, $this->getArgExprs($expr->args));
		}

		if ($expr instanceof ArrayDimFetch) {
			$subExprs = [$expr->var];
			if ($expr->dim !== null) {
				$subExprs[] = $expr->dim;
			}

			return $subExprs;
		}

		if ($expr instanceof PropertyFetch || $expr instanceof NullsafePropertyFetch) {
			$subExprs = [$expr->var];
			if ($expr->name instanceof Expr) {
				$subExprs[] = $expr->name;
			}

			return $subExprs;
		}

		if ($expr instanceof StaticPropertyFetch) {
			$subExprs = [];
			if ($expr->class instanceof Expr) {
				$subExprs[] = $expr->class;
			}
			if ($expr->name instanceof Expr) {
				$subExprs[] = $expr->name;
			}

			return $subExprs;
		}

		if (
			$expr instanceof Cast
			|| $expr instanceof UnaryMinus
			|| $expr instanceof UnaryPlus
			|| $expr instanceof BooleanNot
			|| $expr instanceof BitwiseNot
			|| $expr instanceof Print_
			|| $expr instanceof Clone_
			|| $expr instanceof ErrorSuppress
			|| $expr instanceof Throw_
		) {
			return [$expr->expr];
		}

		if ($expr instanceof Exit_) {
			return $expr->expr !== null ? [$expr->expr] : [];
		}

		if ($expr instanceof Array_) {
			$subExprs = [];
			foreach ($expr->items as $item) {
				if ($item->key !== null) {
					$subExprs[] = $item->key;
				}
				$subExprs[] = $item->value;
			}

			return $subExprs;
		}

		return [];
	}

	/**
	 * @param array<Arg|VariadicPlaceholder> $args
	 * @return Expr[]
	 */
	private function getArgExprs(array $args): array
	{
		$exprs = [];
		foreach ($args as $arg) {
			if (!$arg instanceof Arg) {
				continue;
			}
			$exprs[] = $arg->value;
		}

		return $exprs;
	}

	private function alwaysStops(Expr $expr): bool
	{
		return $expr instanceof Exit_ || $expr instanceof Throw_;
	}

}
