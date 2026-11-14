<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Traits\OperatorTypeTrait;
use function array_filter;
use function array_map;
use function assert;
use function count;
use function get_class;
use function is_int;
use function is_numeric;
use function is_string;
use function max;
use function min;
use function sprintf;
use const NAN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/** @api */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/IntegerRangeType.cpp')]
class IntegerRangeType extends IntegerType implements CompoundType
{

	use OperatorTypeTrait;

	private function __construct(private ?int $min, private ?int $max)
	{
		parent::__construct();
		assert($min === null || $max === null || $min <= $max);
		assert($min !== null || $max !== null);
	}

	public static function fromInterval(?int $min, ?int $max, int $shift = 0): Type
	{
		if ($min !== null && $max !== null) {
			if ($min > $max) {
				return new NeverType();
			}
			if ($min === $max) {
				return new ConstantIntegerType($min + $shift);
			}
		}

		if ($min === null && $max === null) {
			return new IntegerType();
		}

		$range = (new self($min, $max))->shift($shift);
		if (!$range instanceof self) {
			return $range;
		}

		// Nothing is smaller than the smallest integer, and nothing is bigger than the biggest one,
		// so an unbounded side that reaches either one holds a single value.
		if ($range->min === null && $range->max === PHP_INT_MIN) {
			return new ConstantIntegerType(PHP_INT_MIN);
		}
		if ($range->min === PHP_INT_MAX && $range->max === null) {
			return new ConstantIntegerType(PHP_INT_MAX);
		}

		return $range;
	}

	protected static function isDisjoint(?int $minA, ?int $maxA, ?int $minB, ?int $maxB, bool $touchingIsDisjoint = true): bool
	{
		$offset = $touchingIsDisjoint ? 0 : 1;
		return $minA !== null && $maxB !== null && $minA > $maxB + $offset
			|| $maxA !== null && $minB !== null && $maxA + $offset < $minB;
	}

	/**
	 * Return the range of integers smaller than the given value
	 *
	 * @param int|float $value
	 */
	public static function createAllSmallerThan($value): Type
	{
		if (is_int($value)) {
			return self::fromInterval(null, $value, -1);
		}

		return self::fromFloatComparison($value, '<');
	}

	/**
	 * Return the range of integers smaller than or equal to the given value
	 *
	 * @param int|float $value
	 */
	public static function createAllSmallerThanOrEqualTo($value): Type
	{
		if (is_int($value)) {
			return self::fromInterval(null, $value);
		}

		return self::fromFloatComparison($value, '<=');
	}

	/**
	 * Return the range of integers greater than the given value
	 *
	 * @param int|float $value
	 */
	public static function createAllGreaterThan($value): Type
	{
		if (is_int($value)) {
			return self::fromInterval($value, null, 1);
		}

		return self::fromFloatComparison($value, '>');
	}

	/**
	 * Return the range of integers greater than or equal to the given value
	 *
	 * @param int|float $value
	 */
	public static function createAllGreaterThanOrEqualTo($value): Type
	{
		if (is_int($value)) {
			return self::fromInterval($value, null);
		}

		return self::fromFloatComparison($value, '>=');
	}

	/**
	 * The ints for which `$int <$operator> $value` holds. PHP compares an int with a float by converting the
	 * int to float, which rounds ints beyond 2^53 on 64-bit builds (PHP_INT_MAX >= 2.0 ** 63 holds), so the
	 * bound cannot be derived from ceil() or floor() of $value. It is searched for with the comparison itself.
	 *
	 * @param string $operator '<', '<=', '>' or '>='
	 */
	private static function fromFloatComparison(float $value, string $operator): Type
	{
		if ($operator === '<') {
			$holds = static fn (int $int): bool => $int < $value;
		} elseif ($operator === '<=') {
			$holds = static fn (int $int): bool => $int <= $value;
		} elseif ($operator === '>') {
			$holds = static fn (int $int): bool => $int > $value;
		} elseif ($operator === '>=') {
			$holds = static fn (int $int): bool => $int >= $value;
		} else {
			throw new ShouldNotHappenException();
		}

		$holdsAtMin = $holds(PHP_INT_MIN);
		if ($holdsAtMin === $holds(PHP_INT_MAX)) {
			// also a NaN $value, which no comparison holds for
			return $holdsAtMin ? new IntegerType() : new NeverType();
		}

		// $holds() gives $holdsAtMin for $low and the opposite for $high
		$low = PHP_INT_MIN;
		$high = PHP_INT_MAX;
		while (true) {
			// floor(($low + $high) / 2) without overflowing
			$middle = ($low >> 1) + ($high >> 1) + ($low & $high & 1);
			if ($middle === $low) {
				break;
			}
			if ($holds($middle) === $holdsAtMin) {
				$low = $middle;
			} else {
				$high = $middle;
			}
		}

		return $holdsAtMin ? self::fromInterval(null, $low) : self::fromInterval($high, null);
	}

	public function toNumberSet(): NumberSet
	{
		return new NumberSet([[$this->min, $this->max]], [], false);
	}

	public function getMin(): ?int
	{
		return $this->min;
	}

	public function getMax(): ?int
	{
		return $this->max;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('int<%s, %s>', $this->min ?? 'min', $this->max ?? 'max');
	}

	public function shift(int $amount): Type
	{
		if ($amount === 0) {
			return $this;
		}

		$min = $this->min;
		$max = $this->max;

		if ($amount < 0) {
			if ($max !== null) {
				if ($max < PHP_INT_MIN - $amount) {
					return new NeverType();
				}
				$max += $amount;
			}
			if ($min !== null) {
				$min = $min < PHP_INT_MIN - $amount ? null : $min + $amount;
			}
		} else {
			if ($min !== null) {
				if ($min > PHP_INT_MAX - $amount) {
					return new NeverType();
				}
				$min += $amount;
			}
			if ($max !== null) {
				$max = $max > PHP_INT_MAX - $amount ? null : $max + $amount;
			}
		}

		return self::fromInterval($min, $max);
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof parent) {
			return $this->isSuperTypeOf($type)->toAcceptsResult();
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		return AcceptsResult::createNo();
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self || $type instanceof ConstantIntegerType) {
			if ($type instanceof self) {
				$typeMin = $type->min;
				$typeMax = $type->max;
			} else {
				$typeMin = $type->getValue();
				$typeMax = $type->getValue();
			}

			if (self::isDisjoint($this->min, $this->max, $typeMin, $typeMax)) {
				return IsSuperTypeOfResult::createNo();
			}

			if (
				($this->min === null || $typeMin !== null && $this->min <= $typeMin)
				&& ($this->max === null || $typeMax !== null && $this->max >= $typeMax)
			) {
				return IsSuperTypeOfResult::createYes();
			}

			return IsSuperTypeOfResult::createMaybe();
		}

		if ($type instanceof parent) {
			return IsSuperTypeOfResult::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return IsSuperTypeOfResult::createNo();
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if ($otherType instanceof parent) {
			return $otherType->isSuperTypeOf($this);
		}

		if ($otherType instanceof UnionType) {
			return $this->isSubTypeOfUnionWithReason($otherType);
		}

		if ($otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		return IsSuperTypeOfResult::createNo();
	}

	private function isSubTypeOfUnionWithReason(UnionType $otherType): IsSuperTypeOfResult
	{
		if ($this->min !== null && $this->max !== null) {
			$matchingConstantIntegers = array_filter(
				$otherType->getTypes(),
				fn (Type $type): bool => $type instanceof ConstantIntegerType && $type->getValue() >= $this->min && $type->getValue() <= $this->max,
			);

			if (count($matchingConstantIntegers) === ($this->max - $this->min + 1)) {
				return IsSuperTypeOfResult::createYes();
			}
		}

		return IsSuperTypeOfResult::createNo()->or(...array_map(fn (Type $innerType) => $this->isSubTypeOf($innerType), $otherType->getTypes()));
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self && $this->min === $type->min && $this->max === $type->max;
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new IntegerType();
	}

	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		$unorderedComparison = $this->compareEachInteger($otherType, $phpVersion, static fn (ConstantIntegerType $integer): TrinaryLogic => $integer->isSmallerThan($otherType, $phpVersion));
		if ($unorderedComparison !== null) {
			return $unorderedComparison;
		}

		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createYes();
		} else {
			$minIsSmaller = (new ConstantIntegerType($this->min))->isSmallerThan($otherType, $phpVersion);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createNo();
		} else {
			$maxIsSmaller = (new ConstantIntegerType($this->max))->isSmallerThan($otherType, $phpVersion);
		}

		// 0 can have different results in contrast to the interval edges, see https://3v4l.org/iGoti
		$zeroInt = new ConstantIntegerType(0);
		if (!$zeroInt->isSuperTypeOf($this)->no()) {
			return TrinaryLogic::extremeIdentity(
				$zeroInt->isSmallerThan($otherType, $phpVersion),
				$minIsSmaller,
				$maxIsSmaller,
			);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isSmallerThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		$unorderedComparison = $this->compareEachInteger($otherType, $phpVersion, static fn (ConstantIntegerType $integer): TrinaryLogic => $integer->isSmallerThanOrEqual($otherType, $phpVersion));
		if ($unorderedComparison !== null) {
			return $unorderedComparison;
		}

		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createYes();
		} else {
			$minIsSmaller = (new ConstantIntegerType($this->min))->isSmallerThanOrEqual($otherType, $phpVersion);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createNo();
		} else {
			$maxIsSmaller = (new ConstantIntegerType($this->max))->isSmallerThanOrEqual($otherType, $phpVersion);
		}

		// 0 can have different results in contrast to the interval edges, see https://3v4l.org/iGoti
		$zeroInt = new ConstantIntegerType(0);
		if (!$zeroInt->isSuperTypeOf($this)->no()) {
			return TrinaryLogic::extremeIdentity(
				$zeroInt->isSmallerThanOrEqual($otherType, $phpVersion),
				$minIsSmaller,
				$maxIsSmaller,
			);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isGreaterThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		$unorderedComparison = $this->compareEachInteger($otherType, $phpVersion, static fn (ConstantIntegerType $integer): TrinaryLogic => $otherType->isSmallerThan($integer, $phpVersion));
		if ($unorderedComparison !== null) {
			return $unorderedComparison;
		}

		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createNo();
		} else {
			$minIsSmaller = $otherType->isSmallerThan(new ConstantIntegerType($this->min), $phpVersion);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createYes();
		} else {
			$maxIsSmaller = $otherType->isSmallerThan(new ConstantIntegerType($this->max), $phpVersion);
		}

		// 0 can have different results in contrast to the interval edges, see https://3v4l.org/iGoti
		$zeroInt = new ConstantIntegerType(0);
		if (!$zeroInt->isSuperTypeOf($this)->no()) {
			return TrinaryLogic::extremeIdentity(
				$otherType->isSmallerThan($zeroInt, $phpVersion),
				$minIsSmaller,
				$maxIsSmaller,
			);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isGreaterThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		$unorderedComparison = $this->compareEachInteger($otherType, $phpVersion, static fn (ConstantIntegerType $integer): TrinaryLogic => $otherType->isSmallerThanOrEqual($integer, $phpVersion));
		if ($unorderedComparison !== null) {
			return $unorderedComparison;
		}

		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createNo();
		} else {
			$minIsSmaller = $otherType->isSmallerThanOrEqual(new ConstantIntegerType($this->min), $phpVersion);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createYes();
		} else {
			$maxIsSmaller = $otherType->isSmallerThanOrEqual(new ConstantIntegerType($this->max), $phpVersion);
		}

		// 0 can have different results in contrast to the interval edges, see https://3v4l.org/iGoti
		$zeroInt = new ConstantIntegerType(0);
		if (!$zeroInt->isSuperTypeOf($this)->no()) {
			return TrinaryLogic::extremeIdentity(
				$otherType->isSmallerThanOrEqual($zeroInt, $phpVersion),
				$minIsSmaller,
				$maxIsSmaller,
			);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	/**
	 * PHP 8 compares an integer with a non-numeric string as strings, which
	 * does not keep the order of the integers ('10' < '1abc' < '2'), so the
	 * bounds do not decide the comparison then and each integer does.
	 *
	 * @param callable(ConstantIntegerType): TrinaryLogic $compare
	 */
	private function compareEachInteger(Type $otherType, PhpVersion $phpVersion, callable $compare): ?TrinaryLogic
	{
		if (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
			return null;
		}

		$hasNonNumericString = false;
		foreach ($otherType->getConstantScalarValues() as $value) {
			if (is_string($value) && !is_numeric($value)) {
				$hasNonNumericString = true;
				break;
			}
		}
		if (!$hasNonNumericString) {
			return null;
		}

		$integers = $this->getFiniteTypes();
		if ($integers === []) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::extremeIdentity(...array_map($compare, $integers));
	}

	public function getSmallerType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new ConstantBooleanType(true),
			new ConstantFloatType(NAN),
		];

		if ($this->max !== null) {
			$subtractedTypes[] = self::createAllGreaterThanOrEqualTo($this->max);
			$subtractedTypes[] = FloatRangeType::createAllGreaterThanOrEqualTo($this->max);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new ConstantFloatType(NAN),
		];

		if ($this->max !== null) {
			$subtractedTypes[] = self::createAllGreaterThan($this->max);
			$subtractedTypes[] = FloatRangeType::createAllGreaterThan($this->max);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new NullType(),
			new ConstantBooleanType(false),
			new ConstantFloatType(NAN),
		];

		if ($this->min !== null) {
			$subtractedTypes[] = self::createAllSmallerThanOrEqualTo($this->min);
			$subtractedTypes[] = FloatRangeType::createAllSmallerThanOrEqualTo($this->min);
		}

		if ($this->min !== null && $this->min > 0 || $this->max !== null && $this->max < 0) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new ConstantFloatType(NAN),
		];

		if ($this->min !== null) {
			$subtractedTypes[] = self::createAllSmallerThan($this->min);
			$subtractedTypes[] = FloatRangeType::createAllSmallerThan($this->min);
		}

		if ($this->min !== null && $this->min > 0 || $this->max !== null && $this->max < 0) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	/** An int converts to the float closest to it, which keeps the order of the ints. */
	public function toFloat(): Type
	{
		return FloatRangeType::fromInterval((float) ($this->min ?? PHP_INT_MIN), (float) ($this->max ?? PHP_INT_MAX));
	}

	public function toBoolean(): BooleanType
	{
		$isZero = (new ConstantIntegerType(0))->isSuperTypeOf($this);
		if ($isZero->no()) {
			return new ConstantBooleanType(true);
		}

		if ($isZero->maybe()) {
			return new BooleanType();
		}

		return new ConstantBooleanType(false);
	}

	public function toAbsoluteNumber(): Type
	{
		if ($this->min !== null && $this->min >= 0) {
			return $this;
		}

		// Negating the smallest integer overflows, so its absolute value is treated as unbounded,
		// the same way an unbounded lower bound is. This keeps abs(int<min, 0>) and
		// abs(int<-9223372036854775808, 0>) in agreement.
		$inversedMin = $this->min !== null && $this->min !== PHP_INT_MIN ? -$this->min : null;

		if ($this->max === null || $this->max >= 0) {
			return self::fromInterval(0, $inversedMin !== null && $this->max !== null ? max($inversedMin, $this->max) : null);
		}

		return self::fromInterval(-$this->max, $inversedMin);
	}

	public function toBitwiseNotType(): Type
	{
		// ~int<a, b> = int<~b, ~a>  (bitwise NOT reverses the order)
		return self::fromInterval(
			$this->max !== null ? ~$this->max : null,
			$this->min !== null ? ~$this->min : null,
		);
	}

	public function toString(): Type
	{
		$finiteTypes = $this->getFiniteTypes();
		if ($finiteTypes !== []) {
			return TypeCombinator::union(...$finiteTypes)->toString();
		}

		$isZero = (new ConstantIntegerType(0))->isSuperTypeOf($this);
		if ($isZero->no()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryDecimalIntegerStringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}

		return new IntersectionType([
			new StringType(),
			new AccessoryDecimalIntegerStringType(),
		]);
	}

	/**
	 * Return the union with another type, but only if it can be expressed in a simpler way than using UnionType
	 *
	 */
	public function tryUnion(Type $otherType): ?Type
	{
		if ($otherType instanceof self || $otherType instanceof ConstantIntegerType) {
			if ($otherType instanceof self) {
				$otherMin = $otherType->min;
				$otherMax = $otherType->max;
			} else {
				$otherMin = $otherType->getValue();
				$otherMax = $otherType->getValue();
			}

			if (self::isDisjoint($this->min, $this->max, $otherMin, $otherMax, false)) {
				return null;
			}

			return self::fromInterval(
				$this->min !== null && $otherMin !== null ? min($this->min, $otherMin) : null,
				$this->max !== null && $otherMax !== null ? max($this->max, $otherMax) : null,
			);
		}

		if (get_class($otherType) === parent::class) {
			return $otherType;
		}

		return null;
	}

	/**
	 * Return the intersection with another type, but only if it can be expressed in a simpler way than using
	 * IntersectionType
	 *
	 */
	public function tryIntersect(Type $otherType): ?Type
	{
		if ($otherType instanceof self || $otherType instanceof ConstantIntegerType) {
			if ($otherType instanceof self) {
				$otherMin = $otherType->min;
				$otherMax = $otherType->max;
			} else {
				$otherMin = $otherType->getValue();
				$otherMax = $otherType->getValue();
			}

			if (self::isDisjoint($this->min, $this->max, $otherMin, $otherMax, false)) {
				return new NeverType();
			}

			if ($this->min === null) {
				$newMin = $otherMin;
			} elseif ($otherMin === null) {
				$newMin = $this->min;
			} else {
				$newMin = max($this->min, $otherMin);
			}

			if ($this->max === null) {
				$newMax = $otherMax;
			} elseif ($otherMax === null) {
				$newMax = $this->max;
			} else {
				$newMax = min($this->max, $otherMax);
			}

			return self::fromInterval($newMin, $newMax);
		}

		if (get_class($otherType) === parent::class) {
			return $this;
		}

		return null;
	}

	/**
	 * Return the different with another type, or null if it cannot be represented.
	 *
	 */
	public function tryRemove(Type $typeToRemove): ?Type
	{
		if (get_class($typeToRemove) === parent::class) {
			return new NeverType();
		}

		if ($typeToRemove instanceof self || $typeToRemove instanceof ConstantIntegerType) {
			if ($typeToRemove instanceof self) {
				$removeMin = $typeToRemove->min;
				$removeMax = $typeToRemove->max;
			} else {
				$removeMin = $typeToRemove->getValue();
				$removeMax = $typeToRemove->getValue();
			}

			if (
				$this->min !== null && $removeMax !== null && $removeMax < $this->min
				|| $this->max !== null && $removeMin !== null && $this->max < $removeMin
			) {
				return $this;
			}

			if ($removeMin !== null && $removeMin !== PHP_INT_MIN) {
				$lowerPart = self::fromInterval($this->min, $removeMin - 1);
			} else {
				$lowerPart = null;
			}
			if ($removeMax !== null && $removeMax !== PHP_INT_MAX) {
				$upperPart = self::fromInterval($removeMax + 1, $this->max);
			} else {
				$upperPart = null;
			}

			if ($lowerPart !== null && $upperPart !== null) {
				return TypeCombinator::union($lowerPart, $upperPart);
			}

			return $lowerPart ?? $upperPart;
		}

		return null;
	}

	/**
	 * @return list<ConstantIntegerType>
	 */
	public function getFiniteTypes(): array
	{
		if ($this->min === null || $this->max === null) {
			return [];
		}

		$size = $this->max - $this->min;
		if ($size > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return [];
		}

		$types = [];
		for ($i = 0; $i <= $size; $i++) {
			// $this->min + $size is $this->max, so nothing here overflows —
			// unlike $i++ past PHP_INT_MAX, which would turn $i into a float
			$types[] = new ConstantIntegerType($this->min + $i);
		}

		return $types;
	}

	public function toPhpDocNode(): TypeNode
	{
		if ($this->min === null) {
			$min = new IdentifierTypeNode('min');
		} else {
			$min = new ConstTypeNode(new ConstExprIntegerNode((string) $this->min));
		}

		if ($this->max === null) {
			$max = new IdentifierTypeNode('max');
		} else {
			$max = new ConstTypeNode(new ConstExprIntegerNode((string) $this->max));
		}

		return new GenericTypeNode(new IdentifierTypeNode('int'), [$min, $max]);
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		$zeroInt = new ConstantIntegerType(0);
		if ($zeroInt->isSuperTypeOf($this)->no()) {
			if ($type->isTrue()->yes()) {
				return new ConstantBooleanType(true);
			}
			if ($type->isFalse()->yes()) {
				return new ConstantBooleanType(false);
			}
		}

		if (
			$this->isSmallerThan($type, $phpVersion)->yes()
			|| $this->isGreaterThan($type, $phpVersion)->yes()
		) {
			return new ConstantBooleanType(false);
		}

		return parent::looseCompare($type, $phpVersion);
	}

	public function toValueSet(): ValueSet
	{
		return ValueSet::createNumbers($this->toNumberSet());
	}

}
