<?php declare(strict_types = 1);

namespace PHPStan\Type\Enum;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\EnumPropertyReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/** @api */
class EnumCaseObjectType extends ObjectType
{

	public function __construct(
		string $className,
		private string $enumCaseName,
		?ClassReflection $classReflection = null,
	)
	{
		parent::__construct($className, null, $classReflection);
	}

	public function getEnumCaseName(): string
	{
		return $this->enumCaseName;
	}

	public function describe(VerbosityLevel $level): string
	{
		$parent = parent::describe($level);

		return sprintf('%s::%s', $parent, $this->enumCaseName);
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		return $this->getClassName() === $type->getClassName()
			&& $this->enumCaseName === $type->enumCaseName;
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->isSuperTypeOf($type);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return TrinaryLogic::createFromBoolean(
				$this->getClassName() === $type->getClassName()
				&& $this->enumCaseName === $type->enumCaseName,
			);
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		$parent = new parent($this->getClassName(), $this->getSubtractedType(), $this->getClassReflection());

		return $parent->isSuperTypeOf($type)->and(TrinaryLogic::createMaybe());
	}

	public function subtract(Type $type): Type
	{
		return $this;
	}

	public function getTypeWithoutSubtractedType(): Type
	{
		return $this;
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		return $this;
	}

	public function getSubtractedType(): ?Type
	{
		return null;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($this->isSuperTypeOf($typeToRemove)->yes()) {
			return $this->subtract($typeToRemove);
		}

		return null;
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return parent::getProperty($propertyName, $scope);

		}
		if ($propertyName === 'name') {
			return new EnumPropertyReflection($classReflection, new ConstantStringType($this->enumCaseName));
		}

		if ($classReflection->isBackedEnum() && $propertyName === 'value') {
			if ($classReflection->hasEnumCase($this->enumCaseName)) {
				$enumCase = $classReflection->getEnumCase($this->enumCaseName);
				$valueType = $enumCase->getBackingValueType();
				if ($valueType === null) {
					throw new ShouldNotHappenException();
				}

				return new EnumPropertyReflection($classReflection, $valueType);
			}
		}

		return parent::getProperty($propertyName, $scope);
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new parent($this->getClassName(), null, $this->getClassReflection());
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['className'], $properties['enumCaseName'], null);
	}

}
