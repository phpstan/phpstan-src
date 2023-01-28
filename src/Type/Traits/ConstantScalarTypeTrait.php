<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
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
			return AcceptsResult::createFromBoolean($this->value === $type->value);
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return parent::acceptsWithReason($type, $strictTypes)->and(AcceptsResult::createMaybe());
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return $this->value === $type->value ? TrinaryLogic::createYes() : TrinaryLogic::createNo();
		}

		if ($type instanceof parent) {
			return TrinaryLogic::createMaybe();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
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

}
