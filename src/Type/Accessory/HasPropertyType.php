<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\ObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class HasPropertyType implements AccessoryType, CompoundType
{

	use ObjectTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	/** @api */
	public function __construct(private string $propertyName)
	{
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [];
	}

	public function getConstantStrings(): array
	{
		return [];
	}

	public function getPropertyName(): string
	{
		return $this->propertyName;
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->equals($type));
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		return $type->hasProperty($this->propertyName);
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		if ($otherType instanceof self) {
			$limit = TrinaryLogic::createYes();
		} else {
			$limit = TrinaryLogic::createMaybe();
		}

		return $limit->and($otherType->hasProperty($this->propertyName));
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isSubTypeOf($acceptingType);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->propertyName === $type->propertyName;
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('hasProperty(%s)', $this->propertyName);
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		if ($this->propertyName === $propertyName) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return [new TrivialParametersAcceptor()];
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public function exponentiate(Type $exponent): Type
	{
		return new ErrorType();
	}

	public static function __set_state(array $properties): Type
	{
		return new self($properties['propertyName']);
	}

}
