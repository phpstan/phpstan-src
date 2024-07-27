<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function array_filter;
use function assert;
use function ceil;
use function count;
use function floor;
use function get_class;
use function is_float;
use function is_int;
use function max;
use function min;
use function sprintf;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/** @api */
class IntegerRangeType extends IntegerType implements CompoundType
{

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

		return (new self($min, $max))->shift($shift);
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

		if ($value > PHP_INT_MAX) {
			return new IntegerType();
		}

		if ($value <= PHP_INT_MIN) {
			return new NeverType();
		}

		return self::fromInterval(null, (int) ceil($value), -1);
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

		if ($value >= PHP_INT_MAX) {
			return new IntegerType();
		}

		if ($value < PHP_INT_MIN) {
			return new NeverType();
		}

		return self::fromInterval(null, (int) floor($value));
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

		if ($value < PHP_INT_MIN) {
			return new IntegerType();
		}

		if ($value >= PHP_INT_MAX) {
			return new NeverType();
		}

		return self::fromInterval((int) floor($value), null, 1);
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

		if ($value <= PHP_INT_MIN) {
			return new IntegerType();
		}

		if ($value > PHP_INT_MAX) {
			return new NeverType();
		}

		return self::fromInterval((int) ceil($value), null);
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
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof parent) {
			return new AcceptsResult($this->isSuperTypeOf($type), []);
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return AcceptsResult::createNo();
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

		if ($otherType instanceof UnionType) {
			return $this->isSubTypeOfUnion($otherType);
		}

		if ($otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	private function isSubTypeOfUnion(UnionType $otherType): TrinaryLogic
	{
		if ($this->min !== null && $this->max !== null) {
			$matchingConstantIntegers = array_filter(
				$otherType->getTypes(),
				fn (Type $type): bool => $type instanceof ConstantIntegerType && $type->getValue() >= $this->min && $type->getValue() <= $this->max,
			);

			if (count($matchingConstantIntegers) === ($this->max - $this->min + 1)) {
				return TrinaryLogic::createYes();
			}
		}

		return TrinaryLogic::createNo()->lazyOr($otherType->getTypes(), fn (Type $innerType) => $this->isSubTypeOf($innerType));
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
	}

	public function isAcceptedWithReasonBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return new AcceptsResult($this->isSubTypeOf($acceptingType), []);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self && $this->min === $type->min && $this->max === $type->max;
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new IntegerType();
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createYes();
		} else {
			$minIsSmaller = (new ConstantIntegerType($this->min))->isSmallerThan($otherType);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createNo();
		} else {
			$maxIsSmaller = (new ConstantIntegerType($this->max))->isSmallerThan($otherType);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createYes();
		} else {
			$minIsSmaller = (new ConstantIntegerType($this->min))->isSmallerThanOrEqual($otherType);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createNo();
		} else {
			$maxIsSmaller = (new ConstantIntegerType($this->max))->isSmallerThanOrEqual($otherType);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isGreaterThan(Type $otherType): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createNo();
		} else {
			$minIsSmaller = $otherType->isSmallerThan((new ConstantIntegerType($this->min)));
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createYes();
		} else {
			$maxIsSmaller = $otherType->isSmallerThan((new ConstantIntegerType($this->max)));
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createNo();
		} else {
			$minIsSmaller = $otherType->isSmallerThanOrEqual((new ConstantIntegerType($this->min)));
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createYes();
		} else {
			$maxIsSmaller = $otherType->isSmallerThanOrEqual((new ConstantIntegerType($this->max)));
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function getSmallerType(): Type
	{
		$subtractedTypes = [
			new ConstantBooleanType(true),
		];

		if ($this->max !== null) {
			$subtractedTypes[] = self::createAllGreaterThanOrEqualTo($this->max);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getSmallerOrEqualType(): Type
	{
		$subtractedTypes = [];

		if ($this->max !== null) {
			$subtractedTypes[] = self::createAllGreaterThan($this->max);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterType(): Type
	{
		$subtractedTypes = [
			new NullType(),
			new ConstantBooleanType(false),
		];

		if ($this->min !== null) {
			$subtractedTypes[] = self::createAllSmallerThanOrEqualTo($this->min);
		}

		if ($this->min !== null && $this->min > 0 || $this->max !== null && $this->max < 0) {
			$subtractedTypes[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
	}

	public function getGreaterOrEqualType(): Type
	{
		$subtractedTypes = [];

		if ($this->min !== null) {
			$subtractedTypes[] = self::createAllSmallerThan($this->min);
		}

		if ($this->min !== null && $this->min > 0 || $this->max !== null && $this->max < 0) {
			$subtractedTypes[] = new NullType();
			$subtractedTypes[] = new ConstantBooleanType(false);
		}

		return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
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

		if ($this->max === null || $this->max >= 0) {
			$inversedMin = $this->min !== null ? $this->min * -1 : null;

			return self::fromInterval(0, $inversedMin !== null && $this->max !== null ? max($inversedMin, $this->max) : null);
		}

		return self::fromInterval($this->max * -1, $this->min !== null ? $this->min * -1 : null);
	}

	public function toString(): Type
	{
		$isZero = (new ConstantIntegerType(0))->isSuperTypeOf($this);
		if ($isZero->no()) {
			return new IntersectionType([
				new StringType(),
				new AccessoryNumericStringType(),
				new AccessoryNonFalsyStringType(),
			]);
		}

		return new IntersectionType([
			new StringType(),
			new AccessoryNumericStringType(),
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

	public function exponentiate(Type $exponent): Type
	{
		if ($exponent instanceof UnionType) {
			$results = [];
			foreach ($exponent->getTypes() as $unionType) {
				$results[] = $this->exponentiate($unionType);
			}
			return TypeCombinator::union(...$results);
		}

		if ($exponent instanceof IntegerRangeType) {
			$min = null;
			$max = null;
			if ($this->getMin() !== null && $exponent->getMin() !== null) {
				$min = $this->getMin() ** $exponent->getMin();
			}
			if ($this->getMax() !== null && $exponent->getMax() !== null) {
				$max = $this->getMax() ** $exponent->getMax();
			}

			if (($min !== null || $max !== null) && !is_float($min) && !is_float($max)) {
				return self::fromInterval($min, $max);
			}
		}

		if ($exponent instanceof ConstantScalarType) {
			$exponentValue = $exponent->getValue();
			if (is_int($exponentValue)) {
				$min = null;
				$max = null;
				if ($this->getMin() !== null) {
					$min = $this->getMin() ** $exponentValue;
				}
				if ($this->getMax() !== null) {
					$max = $this->getMax() ** $exponentValue;
				}

				if (!is_float($min) && !is_float($max)) {
					return self::fromInterval($min, $max);
				}
			}
		}

		return parent::exponentiate($exponent);
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
		for ($i = $this->min; $i <= $this->max; $i++) {
			$types[] = new ConstantIntegerType($i);
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

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['min'], $properties['max']);
	}

}
