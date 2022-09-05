<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr;

class NullsafeCheck
{

	public function containsNullSafe(Expr $expr): bool
	{
		if (
			$expr instanceof Expr\NullsafePropertyFetch
			|| $expr instanceof Expr\NullsafeMethodCall
		) {
			return true;
		}

		if ($expr instanceof Expr\ArrayDimFetch) {
			return $this->containsNullSafe($expr->var);
		}

		if ($expr instanceof Expr\PropertyFetch) {
			return $this->containsNullSafe($expr->var);
		}

		if ($expr instanceof Expr\StaticPropertyFetch && $expr->class instanceof Expr) {
			return $this->containsNullSafe($expr->class);
		}

		if ($expr instanceof Expr\MethodCall) {
			return $this->containsNullSafe($expr->var);
		}

		if ($expr instanceof Expr\StaticCall && $expr->class instanceof Expr) {
			return $this->containsNullSafe($expr->class);
		}

		if ($expr instanceof Expr\List_) {
			foreach ($expr->items as $item) {
				if ($item === null) {
					continue;
				}

				if ($item->key !== null && $this->containsNullSafe($item->key)) {
					return true;
				}

				if ($this->containsNullSafe($item->value)) {
					return true;
				}
			}
		}

		return false;
	}

}
