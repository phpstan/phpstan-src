<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;

/**
 * A single normalized form of an array callback: the variables that stand for
 * the element value and key, and the predicate expression evaluated against
 * them. A constant-string callable that resolves to a union of names produces
 * several predicates.
 *
 * A null `$expr` marks a callable that cannot be turned into a predicate at all
 * (e.g. an empty or invalid function name) - consumers decide whether that
 * means "error" (array_filter) or "no narrowing" (array_all / array_any).
 */
final class ArrayCallbackPredicate
{

	public function __construct(
		private ?Variable $itemVar,
		private ?Variable $keyVar,
		private ?Expr $expr,
	)
	{
	}

	public function getItemVar(): ?Variable
	{
		return $this->itemVar;
	}

	public function getKeyVar(): ?Variable
	{
		return $this->keyVar;
	}

	public function getExpr(): ?Expr
	{
		return $this->expr;
	}

}
