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
use PHPStan\Type\LooseComparisonHelper;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;

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

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		if (!$this instanceof ConstantScalarType) {
			throw new ShouldNotHappenException();
		}

		if ($type instanceof ConstantScalarType) {
			return LooseComparisonHelper::compareConstantScalars($this, $type, $phpVersion);
		}

		$constantScalars = TypeUtils::getConstantScalars($type);
		if (count($constantScalars) > 0) {
			$results = [];
			foreach ($constantScalars as $scalarType) {
				$results[] = $this->looseCompare($scalarType, $phpVersion);
			}

			$result = TypeCombinator::union(...$results);
			if (!$result instanceof BooleanType) {
				throw new ShouldNotHappenException();
			}
			return $result;
		}

		$zeroBool = LooseComparisonHelper::compareZero($this, $type, $phpVersion);
		if ($zeroBool !== null) {
			return $zeroBool;
		}

		if ($type->isArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
			// @phpstan-ignore-next-line
			return new ConstantBooleanType($this->getValue() == []); // phpcs:ignore
		}
		if ($type->isArray()->yes() && $type->isIterableAtLeastOnce()->yes()) {
			// @phpstan-ignore-next-line
			return new ConstantBooleanType($this->getValue() != []); // phpcs:ignore
		}

		if ($type->isString()->yes() && $type->isNonEmptyString()->no()) {
			// @phpstan-ignore-next-line
			return new ConstantBooleanType($this->getValue() == ''); // phpcs:ignore
		}
		if ($type->isNonEmptyString()->yes()) {
			// @phpstan-ignore-next-line
			return new ConstantBooleanType($this->getValue() != ''); // phpcs:ignore
		}

		if ($type->isObject()->yes()) {
			return $type->looseCompare($this, $phpVersion);
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

}
