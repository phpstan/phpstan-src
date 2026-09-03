<?php declare(strict_types = 1);

namespace PHPStan\Type\Traverser;

use PHPStan\DependencyInjection\ReportUnsafeArrayStringKeyCastingToggle;
use PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeTraverserCallable;
use PHPStan\Type\UnionType;

/**
 * PHP casts a decimal-integer string array key ("123") to int, so an array with
 * a `string` key type can hand back an int. There are two places that matters,
 * and they widen differently on purpose.
 *
 * {@see self::castKeyType()} widens the key type an array *has*. That type also
 * decides how the array describes itself and what it accepts, so only
 * `reportUnsafeArrayStringKeyCasting: detect` widens there — it opts into the
 * resulting reports. Under `prevent` there is nothing to do, because PHPDoc
 * string key types are narrowed to `non-decimal-int-string` when resolved.
 *
 * {@see self::castReadKeyType()} widens a key *taken out* of an array and handed
 * back as a value of its own — `array_key_first()`, `array_keys()`, `key()`,
 * `array_flip()`, … With the toggle off it widens `string` to the benevolent
 * `(int|string)`, which stops `array_key_first([$string => null])` from looking
 * like a certain `string` without making either branch report an error.
 *
 * `foreach` keys are deliberately not widened with the toggle off: the key
 * usually goes straight back into another array (`$result[$k] = …`), and a
 * benevolent `(int|string)` key collapses that array to `array<mixed, …>`.
 * `detect` is the level that gets accurate `foreach` keys.
 *
 * Both are shared by ArrayType and ConstantArrayType so the two representations
 * agree — otherwise comparing a general array (cast key) against a
 * constant-array shape (raw key) yields a spurious `Maybe`.
 */
final class UnsafeArrayStringKeyCastingTraverser implements TypeTraverserCallable
{

	private function __construct(private bool $precise)
	{
	}

	public static function castKeyType(Type $keyType): Type
	{
		if (ReportUnsafeArrayStringKeyCastingToggle::getLevel() !== ReportUnsafeArrayStringKeyCastingToggle::DETECT) {
			return $keyType;
		}

		return TypeTraverser::map($keyType, new self(true));
	}

	public static function castReadKeyType(Type $keyType): Type
	{
		$level = ReportUnsafeArrayStringKeyCastingToggle::getLevel();
		if ($level !== null) {
			// `detect` already widened the key type the array carries, and `prevent`
			// made sure it can't hold a decimal-integer string in the first place.
			return self::castKeyType($keyType);
		}

		// A key type that already covers int has nothing to gain from the widening.
		// Leaving it alone also keeps it out of a BenevolentUnionType, so what is
		// checked against it stays as strict as it is today.
		if ($keyType->isSuperTypeOf(new IntegerType())->yes()) {
			return $keyType;
		}

		return TypeTraverser::map($keyType, new self(false));
	}

	/**
	 * Adds the "there is no key" result an accessor returns for an empty array
	 * (`null` for array_key_first(), `false` for array_search(), …).
	 *
	 * TypeCombinator alone would drop the benevolence of a widened key type and
	 * start reporting on the very code the widening exists to leave alone.
	 */
	public static function unionWithReadKeyType(Type $keyType, Type $noKeyType): Type
	{
		$keyType = self::castReadKeyType($keyType);
		$union = TypeCombinator::union($keyType, $noKeyType);
		if ($keyType instanceof BenevolentUnionType && $union instanceof UnionType && !$union instanceof BenevolentUnionType) {
			return new BenevolentUnionType($union->getTypes());
		}

		return $union;
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
			if (!$this->precise) {
				if ($type->isDecimalIntegerString()->yes()) {
					return new IntegerType();
				}

				return new BenevolentUnionType([new IntegerType(), $type]);
			}

			return TypeCombinator::union(
				new IntegerType(),
				TypeCombinator::intersect($type, new AccessoryDecimalIntegerStringType(inverse: true)),
			);
		}

		return $type;
	}

}
