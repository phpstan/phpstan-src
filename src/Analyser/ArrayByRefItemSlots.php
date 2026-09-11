<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Type\IntegerType;
use function count;
use function is_int;

/**
 * Resolves the `&` items of an array literal to the array slots they alias.
 *
 * The slot keeps aliasing the referenced expression for as long as it exists - copying
 * the array preserves its reference items - so a write into the slot is a write into
 * the referenced expression and the other way around.
 */
final class ArrayByRefItemSlots
{

	/**
	 * Slots aliased by the `&` items of $array, including the items of nested array literals.
	 * The returned slot expressions are dim fetches rooted at $rootExpr.
	 *
	 * @return list<array{Expr, ArrayDimFetch}> pairs of [referenced expression, slot expression]
	 */
	public static function resolve(Scope $scope, Expr\Array_ $array, Expr $rootExpr): array
	{
		$slots = [];
		self::collect($scope, $array, $rootExpr, $slots);

		return $slots;
	}

	/**
	 * @param list<array{Expr, ArrayDimFetch}> $slots
	 * @param-out list<array{Expr, ArrayDimFetch}> $slots
	 */
	private static function collect(Scope $scope, Expr\Array_ $array, Expr $parentExpr, array &$slots): void
	{
		$implicitIndex = 0;
		foreach ($array->items as $arrayItem) {
			if ($arrayItem->unpack) {
				// The unpacked array shifts every subsequent implicit index by an
				// unknown amount, and its own items cannot be referenced from here.
				$implicitIndex = null;
				continue;
			}

			if ($arrayItem->key !== null) {
				$keyType = $scope->getType($arrayItem->key)->toArrayKey();

				if ($implicitIndex !== null) {
					$keyValues = $keyType->getConstantScalarValues();
					if (count($keyValues) === 1) {
						$keyValue = $keyValues[0];
						if (is_int($keyValue) && $keyValue >= $implicitIndex) {
							$implicitIndex = $keyValue + 1;
						}
					} elseif (!$keyType->isInteger()->no()) {
						// Key could be an integer, but we don't know which one,
						// so subsequent implicit indices are unpredictable
						$implicitIndex = null;
					}
				}

				$dimExpr = $arrayItem->key;
			} elseif ($implicitIndex !== null) {
				$dimExpr = new Node\Scalar\Int_($implicitIndex);
				$implicitIndex++;
			} else {
				$dimExpr = new TypeExpr(new IntegerType());
			}

			$dimFetchExpr = new ArrayDimFetch($parentExpr, $dimExpr);

			if ($arrayItem->value instanceof Expr\Array_) {
				self::collect($scope, $arrayItem->value, $dimFetchExpr, $slots);
				continue;
			}

			if (!$arrayItem->byRef) {
				continue;
			}

			$slots[] = [$arrayItem->value, $dimFetchExpr];
		}
	}

}
