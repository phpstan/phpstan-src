<?php declare(strict_types = 1);

namespace PHPStan\Type\Enum;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\Php\EnumPropertyReflection;
use PHPStan\Reflection\Php\EnumUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\SubtractableType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/** @api */
class EnumCaseObjectType extends ObjectType
{

	/** @api */
	public function __construct(
		string $className,
		private string $enumCaseName,
		?ClassReflection $classReflection = null,
	)
	{
		parent::__construct($className, classReflection: $classReflection);
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

		return $this->enumCaseName === $type->enumCaseName &&
			$this->getClassName() === $type->getClassName();
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		return $this->isSuperTypeOf($type)->toAcceptsResult();
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			return IsSuperTypeOfResult::createFromBoolean(
				$this->enumCaseName === $type->enumCaseName && $this->getClassName() === $type->getClassName(),
			);
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if (
			$type instanceof SubtractableType
			&& $type->getSubtractedType() !== null
		) {
			$isSuperType = $type->getSubtractedType()->isSuperTypeOf($this);
			if ($isSuperType->yes()) {
				return IsSuperTypeOfResult::createNo();
			}
		}

		$parent = new parent($this->getClassName(), $this->getSubtractedType(), $this->getClassReflection());

		return $parent->isSuperTypeOf($type)->and(IsSuperTypeOfResult::createMaybe());
	}

	public function subtract(Type $type): Type
	{
		return $this->changeSubtractedType($type);
	}

	public function getTypeWithoutSubtractedType(): Type
	{
		return $this;
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		if ($subtractedType === null || ! $this->equals($subtractedType)) {
			return $this;
		}

		return new NeverType();
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

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->getUnresolvedInstancePropertyPrototype($propertyName, $scope);
	}

	public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return parent::getUnresolvedInstancePropertyPrototype($propertyName, $scope);

		}
		if ($propertyName === 'name') {
			return new EnumUnresolvedPropertyPrototypeReflection(
				new EnumPropertyReflection($propertyName, $classReflection, new ConstantStringType($this->enumCaseName)),
			);
		}

		if ($classReflection->isBackedEnum() && $propertyName === 'value') {
			if ($classReflection->hasEnumCase($this->enumCaseName)) {
				$enumCase = $classReflection->getEnumCase($this->enumCaseName);
				$valueType = $enumCase->getBackingValueType();
				if ($valueType === null) {
					throw new ShouldNotHappenException();
				}

				return new EnumUnresolvedPropertyPrototypeReflection(
					new EnumPropertyReflection($propertyName, $classReflection, $valueType),
				);
			}
		}

		return parent::getUnresolvedInstancePropertyPrototype($propertyName, $scope);
	}

	public function hasStaticProperty(string $propertyName): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getBackingValueType(): ?Type
	{
		$classReflection = $this->getClassReflection();
		if ($classReflection === null) {
			return null;
		}

		if (!$classReflection->isBackedEnum()) {
			return null;
		}

		if ($classReflection->hasEnumCase($this->enumCaseName)) {
			$enumCase = $classReflection->getEnumCase($this->enumCaseName);

			return $enumCase->getBackingValueType();
		}

		return null;
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new parent($this->getClassName(), null, $this->getClassReflection());
	}

	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isSmallerThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getEnumCases(): array
	{
		return [$this];
	}

	public function toPhpDocNode(): TypeNode
	{
		return new ConstTypeNode(
			new ConstFetchNode(
				$this->getClassName(),
				$this->getEnumCaseName(),
			),
		);
	}

}
