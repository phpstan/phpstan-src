<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantStringType;

/**
 * Bits of Type::getStringRefinement(): the strings split into cells that the
 * string accessory types never split further. Every refinement of string - a
 * conjunction of accessories - holds whole cells, so the union of two
 * refinements is a refinement exactly when some conjunction holds the cells of
 * both, and a constant string fills a cell only when it is the cell's sole value
 * (the empty string and '0').
 *
 * Whether a string is a literal-string is not decided by its value, so every
 * cell of values comes twice: the low bits are the literal strings, the same
 * bits shifted by NON_LITERAL_SHIFT the others. The constants below name the
 * cells of values; of() makes the cells of both halves from them.
 *
 * @api
 */
final class StringRefinement
{

	/** '' */
	public const EMPTY = 1;
	/** '0' */
	public const ZERO = 2;
	/** the other decimal-int-strings, such as '1' or '-7' */
	public const DECIMAL = 4;
	/** numeric-strings that are not decimal-int-strings, of either case, such as '1.5' */
	public const NUMERIC = 8;
	/** ... lowercase only, such as '1e5' */
	public const NUMERIC_LOWER = 16;
	/** ... uppercase only, such as '1E5' */
	public const NUMERIC_UPPER = 32;
	/** non-empty strings that are not numeric, of either case, such as '-' */
	public const TEXT = 64;
	/** ... lowercase only, such as 'a' */
	public const TEXT_LOWER = 128;
	/** ... uppercase only, such as 'A' */
	public const TEXT_UPPER = 256;
	/** ... neither, such as 'aA' */
	public const TEXT_MIXED = 512;

	public const NON_LITERAL_SHIFT = 10;

	public const VALUES = 1023;
	public const LITERAL = self::VALUES;
	public const ALL = self::VALUES | (self::VALUES << self::NON_LITERAL_SHIFT);

	/** The cells of the strings of these values, literal or not. */
	public static function of(int $values): int
	{
		return $values | ($values << self::NON_LITERAL_SHIFT);
	}

	/**
	 * The cell of a constant string, a literal one. Its predicates decide it, so
	 * the rules of what is numeric or lowercase stay where they are defined.
	 */
	public static function ofConstant(ConstantStringType $string): int
	{
		$value = $string->getValue();
		if ($value === '') {
			return self::EMPTY;
		}
		if ($value === '0') {
			return self::ZERO;
		}
		if ($string->isDecimalIntegerString()->yes()) {
			return self::DECIMAL;
		}

		$lower = $string->isLowercaseString()->yes();
		$upper = $string->isUppercaseString()->yes();
		if ($string->isNumericString()->yes()) {
			return $lower ? ($upper ? self::NUMERIC : self::NUMERIC_LOWER) : self::NUMERIC_UPPER;
		}
		if ($lower) {
			return $upper ? self::TEXT : self::TEXT_LOWER;
		}

		return $upper ? self::TEXT_UPPER : self::TEXT_MIXED;
	}

	/** Whether the cells are the sole value of a constant string. */
	public static function isSingleValue(int $cells): bool
	{
		return $cells === self::EMPTY || $cells === self::ZERO;
	}

	/**
	 * The refinement of string holding exactly these cells, or null when no
	 * conjunction of the string accessories does.
	 */
	public static function toType(int $cells): ?Type
	{
		$accessories = [];
		$held = self::ALL;
		foreach (self::getAccessories() as $accessory) {
			$accessoryCells = $accessory->getStringRefinement();
			if ($accessoryCells === null || ($cells & ~$accessoryCells) !== 0) {
				continue;
			}

			$accessories[] = $accessory;
			$held &= $accessoryCells;
		}

		if ($held !== $cells) {
			return null;
		}

		// an accessory another one implies says nothing more: decimal-int-string is lowercase
		$minimal = [];
		foreach ($accessories as $i => $accessory) {
			$accessoryCells = $accessory->getStringRefinement() ?? self::ALL;
			foreach ($accessories as $j => $other) {
				$otherCells = $other->getStringRefinement() ?? self::ALL;
				if ($i !== $j && $otherCells !== $accessoryCells && ($otherCells & ~$accessoryCells) === 0) {
					continue 2;
				}
			}
			$minimal[] = $accessory;
		}

		return TypeCombinator::intersect(new StringType(), ...$minimal);
	}

	/** @return list<Type> */
	private static function getAccessories(): array
	{
		return [
			new AccessoryNonEmptyStringType(),
			new AccessoryNonFalsyStringType(),
			new AccessoryNumericStringType(),
			new AccessoryDecimalIntegerStringType(),
			new AccessoryDecimalIntegerStringType(inverse: true),
			new AccessoryLowercaseStringType(),
			new AccessoryUppercaseStringType(),
			new AccessoryLiteralStringType(),
		];
	}

}
