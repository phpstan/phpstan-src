<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;

class IntegerRangeType extends IntegerType implements CompoundType
{

	private ?int $min;

	private ?int $max;

	private function __construct(?int $min, ?int $max)
	{
		assert($min === null || $max === null || $min <= $max);
		assert($min !== null || $max !== null);

		$this->min = $min;
		$this->max = $max;
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

		return (new self($min, $max))->shift($shift);
	}

	protected static function isDisjoint(?int $minA, ?int $maxA, ?int $minB, ?int $maxB, bool $touchingIsDisjoint = true): bool
	{
		$offset = $touchingIsDisjoint ? 0 : 1;
		return $minA !== null && $maxB !== null && $minA > $maxB + $offset
			|| $maxA !== null && $minB !== null && $maxA + $offset < $minB;
	}

	/**
	 * Return the range of integers smaller than (or equal to) the given value
	 *
	 * @param int|float $value
	 * @param bool $orEqual
	 * @return Type
	 */
	public static function createAllSmallerThan($value, bool $orEqual = false): Type
	{
		if (is_int($value)) {
			return self::fromInterval(null, $value, $orEqual ? 0 : -1);
		}

		if ($value > PHP_INT_MAX || $value >= PHP_INT_MAX && $orEqual) {
			return new IntegerType();
		}

		if ($value < PHP_INT_MIN || $value <= PHP_INT_MIN && !$orEqual) {
			return new NeverType();
		}

		if ($orEqual) {
			return self::fromInterval(null, (int) floor($value));
		}

		return self::fromInterval(null, (int) ceil($value), -1);
	}

	/**
	 * Return the range of integers greater than (or equal to) the given value
	 *
	 * @param int|float $value
	 * @param bool $orEqual
	 * @return Type
	 */
	public static function createAllGreaterThan($value, bool $orEqual = false): Type
	{
		if (is_int($value)) {
			return self::fromInterval($value, null, $orEqual ? 0 : 1);
		}

		if ($value < PHP_INT_MIN || $value <= PHP_INT_MIN && $orEqual) {
			return new IntegerType();
		}

		if ($value > PHP_INT_MAX || $value >= PHP_INT_MAX && !$orEqual) {
			return new NeverType();
		}

		if ($orEqual) {
			return self::fromInterval((int) ceil($value), null);
		}

		return self::fromInterval((int) floor($value), null, 1);
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


	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof parent) {
			return $this->isSuperTypeOf($type);
		}

		if ($type instanceof CompoundType) {
			return CompoundTypeHelper::accepts($type, $this, $strictTypes);
		}

		return TrinaryLogic::createNo();
	}


	public function isSuperTypeOf(Type $type): TrinaryLogic
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
				return TrinaryLogic::createNo();
			}

			if (
				($this->min === null || $typeMin !== null && $this->min <= $typeMin)
				&& ($this->max === null || $typeMax !== null && $this->max >= $typeMax)
			) {
				return TrinaryLogic::createYes();
			}

			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof parent) {
			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof parent) {
			return $otherType->isSuperTypeOf($this);
		}

		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isSubTypeOf($acceptingType);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self && $this->min === $type->min && $this->max === $type->max;
	}


	public function generalize(): Type
	{
		return new parent();
	}

	public function isSmallerThan(Type $otherType, bool $orEqual = false): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createYes();
		} else {
			$minIsSmaller = (new ConstantIntegerType($this->min))->isSmallerThan($otherType, $orEqual);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createNo();
		} else {
			$maxIsSmaller = (new ConstantIntegerType($this->max))->isSmallerThan($otherType, $orEqual);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isGreaterThan(Type $otherType, bool $orEqual = false): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createNo();
		} else {
			$minIsSmaller = $otherType->isSmallerThan((new ConstantIntegerType($this->min)), $orEqual);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createYes();
		} else {
			$maxIsSmaller = $otherType->isSmallerThan((new ConstantIntegerType($this->max)), $orEqual);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function getSmallerType(bool $orEqual = false): Type
	{
		$subtractedTypes = [];

		if ($this->max !== null) {
			$subtractedTypes[] = self::createAllGreaterThan($this->max, !$orEqual);
		}

		if (!$orEqual) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterType(bool $orEqual = false): Type
	{
		$subtractedTypes = [];

		if ($this->min !== null) {
			$subtractedTypes[] = self::createAllSmallerThan($this->min, !$orEqual);
		}

		$alwaysTruthy = $this->min !== null && $this->min > 0 || $this->max !== null && $this->max < 0;

		if ($alwaysTruthy || !$orEqual) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		if ($alwaysTruthy && !$orEqual) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function toNumber(): Type
	{
		return new parent();
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

	/**
	 * Return the union with another type, but only if it can be expressed in a simpler way than using UnionType
	 *
	 * @param Type $otherType
	 * @return Type|null
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
				$this->max !== null && $otherMax !== null ? max($this->max, $otherMax) : null
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
	 * @param Type $otherType
	 * @return Type|null
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
	 * @param Type $typeToRemove
	 * @return Type|null
	 */
	public function tryRemove(Type $typeToRemove): ?Type
	{
		if (get_class($typeToRemove) === parent::class) {
			return new NeverType();
		}

		if ($typeToRemove instanceof IntegerRangeType || $typeToRemove instanceof ConstantIntegerType) {
			if ($typeToRemove instanceof IntegerRangeType) {
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
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['min'], $properties['max']);
	}

}
