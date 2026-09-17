<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function array_map;
use function assert;
use function count;
use function in_array;
use function max;
use function min;

#[AutowiredService]
final class RandomIntRangeHelper
{

	/**
	 * Widest possible range of integers a random number generator bounded by
	 * $minType and $maxType can produce.
	 */
	public function createRange(Type $minType, Type $maxType): Type
	{
		$minValues = array_map(
			static function (Type $type): ?int {
				if ($type instanceof IntegerRangeType) {
					return $type->getMin();
				}
				if ($type instanceof ConstantIntegerType) {
					return $type->getValue();
				}
				return null;
			},
			$minType instanceof UnionType ? $minType->getTypes() : [$minType],
		);

		$maxValues = array_map(
			static function (Type $type): ?int {
				if ($type instanceof IntegerRangeType) {
					return $type->getMax();
				}
				if ($type instanceof ConstantIntegerType) {
					return $type->getValue();
				}
				return null;
			},
			$maxType instanceof UnionType ? $maxType->getTypes() : [$maxType],
		);

		assert(count($minValues) > 0);
		assert(count($maxValues) > 0);

		return IntegerRangeType::fromInterval(
			in_array(null, $minValues, true) ? null : min($minValues),
			in_array(null, $maxValues, true) ? null : max($maxValues),
		);
	}

}
