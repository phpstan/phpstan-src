<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\LooseComparisonHelper;
use PHPStan\Type\Type;

trait ConstantScalarTypeTrait
{

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof self) {
			return AcceptsResult::createFromBoolean($this->equals($type));
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return parent::acceptsWithReason($type, $strictTypes)->and(AcceptsResult::createMaybe());
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		return $this->isSuperTypeOfWithReason($type)->result;
	}

	public function isSuperTypeOfWithReason(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			return IsSuperTypeOfResult::createFromBoolean($this->equals($type));
		}

		if ($type instanceof parent) {
			return IsSuperTypeOfResult::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOfWithReason($this);
		}

		return IsSuperTypeOfResult::createNo();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		if (!$this instanceof ConstantScalarType) {
			throw new ShouldNotHappenException();
		}

		if ($type instanceof ConstantScalarType) {
			return LooseComparisonHelper::compareConstantScalars($this, $type, $phpVersion);
		}

		if ($type->isConstantArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
			// @phpstan-ignore equal.notAllowed, equal.invalid
			return new ConstantBooleanType($this->getValue() == []); // phpcs:ignore
		}

		if ($type instanceof CompoundType) {
			return $type->looseCompare($this, $phpVersion);
		}

		return parent::looseCompare($type, $phpVersion);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self && $this->value === $type->value;
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof ConstantScalarType) {
			return TrinaryLogic::createFromBoolean($this->value < $otherType->getValue());
		}

		if ($otherType instanceof CompoundType) {
			return $otherType->isGreaterThan($this);
		}

		return TrinaryLogic::createMaybe();
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof ConstantScalarType) {
			return TrinaryLogic::createFromBoolean($this->value <= $otherType->getValue());
		}

		if ($otherType instanceof CompoundType) {
			return $otherType->isGreaterThanOrEqual($this);
		}

		return TrinaryLogic::createMaybe();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getConstantScalarTypes(): array
	{
		return [$this];
	}

	public function getConstantScalarValues(): array
	{
		return [$this->getValue()];
	}

	public function getFiniteTypes(): array
	{
		return [$this];
	}

}
