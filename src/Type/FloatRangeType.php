<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use function assert;
use function sprintf;
use const PHP_FLOAT_MAX;

/** @api */
class FloatRangeType extends FloatType implements CompoundType
{

	private function __construct(private ?float $min, private ?float $max)
	{
		parent::__construct();
		assert($min === null || $max === null || $min <= $max);
		assert($min !== null || $max !== null);
	}

	public static function fromInterval(?float $min, ?float $max): Type
	{
		if ($min !== null && $max !== null) {
			if ($min > $max) {
				return new NeverType();
			}
			if ($min === $max) {
				return new ConstantFloatType($min);
			}
		}

		if ($min === null && $max === null) {
			return new FloatType();
		}

		return new self($min, $max);
	}

	protected static function isDisjoint(?float $minA, ?float $maxA, ?float $minB, ?float $maxB): bool
	{
		return $minA !== null && $maxB !== null && $minA > $maxB
			|| $maxA !== null && $minB !== null && $maxA < $minB;
	}

	/**
	 * Return the range of floats smaller than the given value
	 */
	public static function createAllSmallerThan(float $value): Type
	{
		if ($value > PHP_FLOAT_MAX) {
			return new FloatType();
		}

		if ($value <= -PHP_FLOAT_MAX) {
			return new NeverType();
		}

		return self::fromInterval(null, $value);
	}

	/**
	 * Return the range of floats smaller than or equal to the given value
	 */
	public static function createAllSmallerThanOrEqualTo(float $value): Type
	{
		if ($value >= PHP_FLOAT_MAX) {
			return new FloatType();
		}

		if ($value < -PHP_FLOAT_MAX) {
			return new NeverType();
		}

		return self::fromInterval(null, $value);
	}

	/**
	 * Return the range of floats greater than the given value
	 */
	public static function createAllGreaterThan(float $value): Type
	{
		if ($value < -PHP_FLOAT_MAX) {
			return new FloatType();
		}

		if ($value >= PHP_FLOAT_MAX) {
			return new NeverType();
		}

		return self::fromInterval($value, null);
	}

	/**
	 * Return the range of floats greater than or equal to the given value
	 */
	public static function createAllGreaterThanOrEqualTo(float $value): Type
	{
		if ($value <= -PHP_FLOAT_MAX) {
			return new FloatType();
		}

		if ($value > PHP_FLOAT_MAX) {
			return new NeverType();
		}

		return self::fromInterval($value, null);
	}

	public function getMin(): ?float
	{
		return $this->min;
	}

	public function getMax(): ?float
	{
		return $this->max;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('float<%s, %s>', $this->min ?? 'min', $this->max ?? 'max');
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
		if ($type instanceof self || $type instanceof ConstantFloatType) {
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

		if ($otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		return TrinaryLogic::createNo();
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
		return new FloatType();
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createYes();
		} else {
			$minIsSmaller = (new ConstantFloatType($this->min))->isSmallerThan($otherType);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createNo();
		} else {
			$maxIsSmaller = (new ConstantFloatType($this->max))->isSmallerThan($otherType);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createYes();
		} else {
			$minIsSmaller = (new ConstantFloatType($this->min))->isSmallerThanOrEqual($otherType);
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createNo();
		} else {
			$maxIsSmaller = (new ConstantFloatType($this->max))->isSmallerThanOrEqual($otherType);
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isGreaterThan(Type $otherType): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createNo();
		} else {
			$minIsSmaller = $otherType->isSmallerThan((new ConstantFloatType($this->min)));
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createYes();
		} else {
			$maxIsSmaller = $otherType->isSmallerThan((new ConstantFloatType($this->max)));
		}

		return TrinaryLogic::extremeIdentity($minIsSmaller, $maxIsSmaller);
	}

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic
	{
		if ($this->min === null) {
			$minIsSmaller = TrinaryLogic::createNo();
		} else {
			$minIsSmaller = $otherType->isSmallerThanOrEqual((new ConstantFloatType($this->min)));
		}

		if ($this->max === null) {
			$maxIsSmaller = TrinaryLogic::createYes();
		} else {
			$maxIsSmaller = $otherType->isSmallerThanOrEqual((new ConstantFloatType($this->max)));
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
		$isZero = (new ConstantFloatType(0.0))->isSuperTypeOf($this);
		if ($isZero->no()) {
			return new ConstantBooleanType(true);
		}

		if ($isZero->maybe()) {
			return new BooleanType();
		}

		return new ConstantBooleanType(false);
	}

	public function toPhpDocNode(): TypeNode
	{
		if ($this->min === null) {
			$min = new IdentifierTypeNode('min');
		} else {
			$min = new ConstTypeNode(new ConstExprFloatNode((string) $this->min));
		}

		if ($this->max === null) {
			$max = new IdentifierTypeNode('max');
		} else {
			$max = new ConstTypeNode(new ConstExprFloatNode((string) $this->max));
		}

		return new GenericTypeNode(new IdentifierTypeNode('float'), [$min, $max]);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['min'], $properties['max']);
	}

}
