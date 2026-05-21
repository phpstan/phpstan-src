<?php declare(strict_types = 1);

namespace PHPStan\Type\Traverser;

use PHPStan\DependencyInjection\ReportUnsafeArrayStringKeyCastingToggle;
use PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeTraverserCallable;
use PHPStan\Type\UnionType;

/**
 * Under `reportUnsafeArrayStringKeyCasting: detect`, PHP casts a decimal-integer
 * string array key ("123") to int when iterating, so the iterable key type widens
 * from `string` to `int | non-decimal-int-string`. Shared by ArrayType and
 * ConstantArrayType so both representations agree — otherwise comparing a general
 * array (cast key) against a constant-array shape (raw key) yields a spurious
 * `Maybe`.
 */
final class UnsafeArrayStringKeyCastingTraverser implements TypeTraverserCallable
{

	public static function castKeyType(Type $keyType): Type
	{
		if (ReportUnsafeArrayStringKeyCastingToggle::getLevel() !== ReportUnsafeArrayStringKeyCastingToggle::DETECT) {
			return $keyType;
		}

		return TypeTraverser::map($keyType, new self());
	}

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function traverse(Type $type, callable $traverse): Type
	{
		if ($type instanceof UnionType) {
			return $traverse($type);
		}

		if ($type->isString()->yes() && !$type->isDecimalIntegerString()->no()) {
			return TypeCombinator::union(
				new IntegerType(),
				TypeCombinator::intersect($type, new AccessoryDecimalIntegerStringType(inverse: true)),
			);
		}

		return $type;
	}

}
