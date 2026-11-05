<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function array_map;
use function get_class;
use function is_finite;
use function is_float;
use function is_int;
use function is_nan;
use function max;
use function min;
use function pack;
use function sprintf;
use function unpack;
use const INF;
use const NAN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * The floats from $min to $max, both included, in PHP's comparison order.
 *
 * The non-NAN floats are a finite ordered set, so every open interval of them
 * is a closed one: $f > 1.5 keeps exactly float<1.5000000000000002, max>.
 * PHP compares -0.0 and 0.0 as equal, so they are one point of that order:
 * a range never holds one zero without the other, and its zero bound is
 * stored as 0.0. NAN is in no range, so float<min, max> is float without NAN.
 *
 * The bounds are mapped to integers that keep this order (see toOrdinal()),
 * which makes the next and the previous float the next and the previous
 * integer, and every interval operation the integer one.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/FloatRangeType.cpp')]
class FloatRangeType extends FloatType implements CompoundType
{

	private function __construct(private float $min, private float $max)
	{
		parent::__construct();
	}

	public static function fromInterval(float $min, float $max): Type
	{
		if (is_nan($min) || is_nan($max)) {
			throw new ShouldNotHappenException();
		}

		$minOrdinal = self::toOrdinal($min);
		$maxOrdinal = self::toOrdinal($max);
		if ($minOrdinal > $maxOrdinal) {
			return new NeverType();
		}

		if ($minOrdinal === $maxOrdinal) {
			return new ConstantFloatType(self::fromOrdinal($minOrdinal));
		}

		return new self(self::fromOrdinal($minOrdinal), self::fromOrdinal($maxOrdinal));
	}

	/**
	 * Maps the non-NAN floats onto integers in their order, both zeros onto 0:
	 * a float's bits read as an integer grow with the float's magnitude.
	 */
	private static function toOrdinal(float $value): int
	{
		if ($value === 0.0) {
			return 0;
		}

		$unpacked = unpack('q', pack('d', $value));
		if ($unpacked === false || !is_int($unpacked[1])) {
			throw new ShouldNotHappenException();
		}

		$bits = $unpacked[1];
		if ($bits >= 0) {
			return $bits;
		}

		return -($bits & PHP_INT_MAX);
	}

	private static function fromOrdinal(int $ordinal): float
	{
		if ($ordinal === 0) {
			return 0.0;
		}

		$unpacked = unpack('d', pack('q', $ordinal < 0 ? -$ordinal : $ordinal));
		if ($unpacked === false || !is_float($unpacked[1])) {
			throw new ShouldNotHappenException();
		}

		return $ordinal < 0 ? -$unpacked[1] : $unpacked[1];
	}

	/**
	 * The bounds of a range or of a non-NAN constant as ordinals.
	 *
	 * @return array{int, int}|null
	 */
	private static function getOrdinalBounds(Type $type): ?array
	{
		if ($type instanceof self) {
			return [self::toOrdinal($type->min), self::toOrdinal($type->max)];
		}

		if ($type instanceof ConstantFloatType && !is_nan($type->getValue())) {
			$ordinal = self::toOrdinal($type->getValue());
			return [$ordinal, $ordinal];
		}

		return null;
	}

	/** The floats smaller than $value, NAN excluded. */
	public static function createAllSmallerThan(float $value): Type
	{
		if (is_nan($value) || $value === -INF) {
			return new NeverType();
		}

		return self::fromInterval(-INF, self::fromOrdinal(self::toOrdinal($value) - 1));
	}

	public static function createAllSmallerThanOrEqualTo(float $value): Type
	{
		if (is_nan($value)) {
			return new NeverType();
		}

		return self::fromInterval(-INF, $value);
	}

	public static function createAllGreaterThan(float $value): Type
	{
		if (is_nan($value) || $value === INF) {
			return new NeverType();
		}

		return self::fromInterval(self::fromOrdinal(self::toOrdinal($value) + 1), INF);
	}

	public static function createAllGreaterThanOrEqualTo(float $value): Type
	{
		if (is_nan($value)) {
			return new NeverType();
		}

		return self::fromInterval($value, INF);
	}

	public function getMin(): float
	{
		return $this->min;
	}

	public function getMax(): float
	{
		return $this->max;
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			static fn (): string => 'float',
			fn (): string => sprintf(
				'float<%s, %s>',
				$this->min === -INF ? 'min' : (new ConstantFloatType($this->min))->describe(VerbosityLevel::precise()),
				$this->max === INF ? 'max' : (new ConstantFloatType($this->max))->describe(VerbosityLevel::precise()),
			),
		);
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		$result = $this->acceptsWithoutReasons($type, $strictTypes);
		if ($result->yes() || (new ConstantFloatType(NAN))->isSuperTypeOf($type)->no()) {
			return $result;
		}

		// tell why when NAN is all that is not accepted
		if (!$this->acceptsWithoutReasons(TypeCombinator::remove($type, new ConstantFloatType(NAN)), $strictTypes)->yes()) {
			return $result;
		}

		return new AcceptsResult($result->result, [
			sprintf('NAN is in no float range. Use %s|NAN to accept it.', $this->describe(VerbosityLevel::precise())),
		]);
	}

	private function acceptsWithoutReasons(Type $type, bool $strictTypes): AcceptsResult
	{
		// an integer is coerced to the float closest to it, which keeps the order of the integers
		if ($type instanceof ConstantIntegerType) {
			return $this->isSuperTypeOf(new ConstantFloatType((float) $type->getValue()))->toAcceptsResult();
		}
		if ($type instanceof IntegerRangeType || get_class($type) === IntegerType::class) {
			$min = $type instanceof IntegerRangeType ? $type->getMin() : null;
			$max = $type instanceof IntegerRangeType ? $type->getMax() : null;

			return $this->isSuperTypeOf(self::fromInterval((float) ($min ?? PHP_INT_MIN), (float) ($max ?? PHP_INT_MAX)))->toAcceptsResult();
		}

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
		$bounds = self::getOrdinalBounds($type);
		if ($bounds !== null) {
			[$typeMin, $typeMax] = $bounds;
			$min = self::toOrdinal($this->min);
			$max = self::toOrdinal($this->max);
			if ($typeMax < $min || $max < $typeMin) {
				return IsSuperTypeOfResult::createNo();
			}

			if ($min <= $typeMin && $typeMax <= $max) {
				return IsSuperTypeOfResult::createYes();
			}

			return IsSuperTypeOfResult::createMaybe();
		}

		// NAN
		if ($type instanceof ConstantFloatType) {
			return IsSuperTypeOfResult::createNo();
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
		if ($otherType instanceof parent || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		if ($otherType instanceof UnionType) {
			return IsSuperTypeOfResult::createNo()->or(...array_map(fn (Type $innerType): IsSuperTypeOfResult => $innerType->isSuperTypeOf($this), $otherType->getTypes()));
		}

		// the other type cannot tell floats apart
		return $otherType->isSuperTypeOf(new FloatType());
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		if ($acceptingType instanceof parent || $acceptingType instanceof IntersectionType) {
			return $acceptingType->isSuperTypeOf($this)->toAcceptsResult();
		}

		if ($acceptingType instanceof UnionType) {
			// AcceptsResult::or() takes a single operand, unlike IsSuperTypeOfResult::or()
			$result = AcceptsResult::createNo();
			foreach ($acceptingType->getTypes() as $innerType) {
				$result = $result->or($innerType->accepts($this, $strictTypes));
			}

			return $result;
		}

		// the other type cannot tell floats apart
		return $acceptingType->accepts(new FloatType(), $strictTypes);
	}

	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->compareEachPoint($otherType, false, false, $phpVersion) ?? parent::isSmallerThan($otherType, $phpVersion);
	}

	public function isSmallerThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->compareEachPoint($otherType, false, true, $phpVersion) ?? parent::isSmallerThanOrEqual($otherType, $phpVersion);
	}

	public function isGreaterThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->compareEachPoint($otherType, true, false, $phpVersion) ?? $otherType->isSmallerThan(new FloatType(), $phpVersion);
	}

	public function isGreaterThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->compareEachPoint($otherType, true, true, $phpVersion) ?? $otherType->isSmallerThanOrEqual(new FloatType(), $phpVersion);
	}

	/**
	 * Compares $otherType with each float of the range, the other type on the
	 * left of `<` or `<=` when $otherOnLeft. Numbers, booleans and null compare
	 * with the floats in their order, so the comparison holds for the whole range
	 * when it holds for both bounds. A bool or null compares by truthiness, which
	 * changes only at zero. Null for other types, which can compare with floats
	 * in any order.
	 */
	private function compareEachPoint(Type $otherType, bool $otherOnLeft, bool $orEqual, PhpVersion $phpVersion): ?TrinaryLogic
	{
		$ordered = TypeCombinator::union(new IntegerType(), new FloatType(), new BooleanType(), new NullType());
		if (!$ordered->isSuperTypeOf($otherType)->yes()) {
			return null;
		}

		$points = [new ConstantFloatType($this->min), new ConstantFloatType($this->max)];
		if (!$this->excludesZero()) {
			$points[] = new ConstantFloatType(0.0);
		}

		$results = [];
		foreach ($points as $point) {
			[$left, $right] = $otherOnLeft ? [$otherType, $point] : [$point, $otherType];
			$results[] = $orEqual ? $left->isSmallerThanOrEqual($right, $phpVersion) : $left->isSmallerThan($right, $phpVersion);
		}

		return TrinaryLogic::extremeIdentity(...$results);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self && $this->min === $type->min && $this->max === $type->max;
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new FloatType();
	}

	public function getSmallerType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new ConstantBooleanType(true),
			new ConstantFloatType(NAN),
			IntegerRangeType::createAllGreaterThanOrEqualTo($this->max),
			self::createAllGreaterThanOrEqualTo($this->max),
		];

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new ConstantFloatType(NAN),
			IntegerRangeType::createAllGreaterThan($this->max),
			self::createAllGreaterThan($this->max),
		];

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new NullType(),
			new ConstantBooleanType(false),
			new ConstantFloatType(NAN),
			IntegerRangeType::createAllSmallerThanOrEqualTo($this->min),
			self::createAllSmallerThanOrEqualTo($this->min),
		];

		if ($this->excludesZero()) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
	{
		$subtractedTypes = [
			new ConstantFloatType(NAN),
			IntegerRangeType::createAllSmallerThan($this->min),
			self::createAllSmallerThan($this->min),
		];

		if ($this->excludesZero()) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	private function excludesZero(): bool
	{
		return self::toOrdinal($this->min) > 0 || self::toOrdinal($this->max) < 0;
	}

	public function toBoolean(): BooleanType
	{
		if ($this->excludesZero()) {
			return new ConstantBooleanType(true);
		}

		return new BooleanType();
	}

	public function toNumberSet(): NumberSet
	{
		return new NumberSet([], [[$this->min, $this->max]], false);
	}

	public function toInteger(): Type
	{
		// casting a float outside of the int range is undefined
		if ($this->min >= (float) PHP_INT_MIN && $this->max < (float) PHP_INT_MAX) {
			return IntegerRangeType::fromInterval((int) $this->min, (int) $this->max);
		}

		return new IntegerType();
	}

	public function toString(): Type
	{
		$types = [new StringType(), new AccessoryUppercaseStringType()];

		// only INF and -INF stringify as non-numeric strings
		if (is_finite($this->min) && is_finite($this->max)) {
			$types[] = new AccessoryNumericStringType();
		} else {
			$types[] = new AccessoryNonEmptyStringType();
		}

		// only 0.0 stringifies as '0', -0.0 is '-0'
		if ($this->excludesZero()) {
			$types[] = new AccessoryNonFalsyStringType();
		}

		return TypeCombinator::intersect(...$types);
	}

	public function toAbsoluteNumber(): Type
	{
		if (self::toOrdinal($this->min) >= 0) {
			return $this;
		}

		if (self::toOrdinal($this->max) <= 0) {
			return self::fromInterval(-$this->max, -$this->min);
		}

		return self::fromInterval(0.0, max(-$this->min, $this->max));
	}

	/**
	 * Return the union with another type, but only if it can be expressed in a simpler way than using UnionType
	 */
	public function tryUnion(Type $otherType): ?Type
	{
		$bounds = self::getOrdinalBounds($otherType);
		if ($bounds !== null) {
			[$otherMin, $otherMax] = $bounds;
			$min = self::toOrdinal($this->min);
			$max = self::toOrdinal($this->max);
			// touching ranges merge: nothing lies between $max and $max + 1
			if ($otherMax < $min - 1 || $max + 1 < $otherMin) {
				return null;
			}

			return self::fromInterval(self::fromOrdinal(min($min, $otherMin)), self::fromOrdinal(max($max, $otherMax)));
		}

		if (get_class($otherType) === parent::class) {
			return $otherType;
		}

		// float is float<min, max>|NAN
		if ($otherType instanceof ConstantFloatType && $this->min === -INF && $this->max === INF) {
			return new FloatType();
		}

		return null;
	}

	/**
	 * Return the intersection with another type, but only if it can be expressed in a simpler way than using
	 * IntersectionType
	 */
	public function tryIntersect(Type $otherType): ?Type
	{
		$bounds = self::getOrdinalBounds($otherType);
		if ($bounds !== null) {
			[$otherMin, $otherMax] = $bounds;

			return self::fromInterval(
				self::fromOrdinal(max(self::toOrdinal($this->min), $otherMin)),
				self::fromOrdinal(min(self::toOrdinal($this->max), $otherMax)),
			);
		}

		// NAN
		if ($otherType instanceof ConstantFloatType) {
			return new NeverType();
		}

		if (get_class($otherType) === parent::class) {
			return $this;
		}

		return null;
	}

	/**
	 * Return the different with another type, or null if it cannot be represented.
	 */
	public function tryRemove(Type $typeToRemove): ?Type
	{
		if (get_class($typeToRemove) === parent::class) {
			return new NeverType();
		}

		$bounds = self::getOrdinalBounds($typeToRemove);
		if ($bounds === null) {
			// NAN is in no range
			return $typeToRemove instanceof ConstantFloatType ? $this : null;
		}

		[$removeMin, $removeMax] = $bounds;
		$min = self::toOrdinal($this->min);
		$max = self::toOrdinal($this->max);
		if ($removeMax < $min || $max < $removeMin) {
			return $this;
		}

		$parts = [];
		if ($min < $removeMin) {
			$parts[] = self::fromInterval($this->min, self::fromOrdinal($removeMin - 1));
		}
		if ($removeMax < $max) {
			$parts[] = self::fromInterval(self::fromOrdinal($removeMax + 1), $this->max);
		}

		return TypeCombinator::union(...$parts);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new GenericTypeNode(new IdentifierTypeNode('float'), [
			$this->min === -INF ? new IdentifierTypeNode('min') : (new ConstantFloatType($this->min))->toPhpDocNode(),
			$this->max === INF ? new IdentifierTypeNode('max') : (new ConstantFloatType($this->max))->toPhpDocNode(),
		]);
	}

}
