<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;

/** @api */
class IntersectionType implements CompoundType
{

	/** @var \PHPStan\Type\Type[] */
	private array $types;

	/**
	 * @api
	 * @param Type[] $types
	 */
	public function __construct(array $types)
	{
		$this->types = UnionTypeHelper::sortTypes($types);
	}

	/**
	 * @return Type[]
	 */
	public function getTypes(): array
	{
		return $this->types;
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return UnionTypeHelper::getReferencedClasses($this->types);
	}

	public function accepts(Type $otherType, bool $strictTypes): TrinaryLogic
	{
		foreach ($this->types as $type) {
			if (!$type->accepts($otherType, $strictTypes)->yes()) {
				return TrinaryLogic::createNo();
			}
		}

		return TrinaryLogic::createYes();
	}

	public function isSuperTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof IntersectionType && $this->equals($otherType)) {
			return TrinaryLogic::createYes();
		}

		$results = [];
		foreach ($this->getTypes() as $innerType) {
			$results[] = $innerType->isSuperTypeOf($otherType);
		}

		return TrinaryLogic::createYes()->and(...$results);
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof self || $otherType instanceof UnionType) {
			return $otherType->isSuperTypeOf($this);
		}

		$results = [];
		foreach ($this->getTypes() as $innerType) {
			$results[] = $otherType->isSuperTypeOf($innerType);
		}

		return TrinaryLogic::maxMin(...$results);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		if ($acceptingType instanceof self || $acceptingType instanceof UnionType) {
			return $acceptingType->isSuperTypeOf($this);
		}

		$results = [];
		foreach ($this->getTypes() as $innerType) {
			$results[] = $acceptingType->accepts($innerType, $strictTypes);
		}

		return TrinaryLogic::maxMin(...$results);
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if (count($this->types) !== count($type->types)) {
			return false;
		}

		foreach ($this->types as $i => $innerType) {
			if (!$innerType->equals($type->types[$i])) {
				return false;
			}
		}

		return true;
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			function () use ($level): string {
				$typeNames = [];
				foreach ($this->types as $type) {
					if ($type instanceof AccessoryType) {
						continue;
					}
					$typeNames[] = TypeUtils::generalizeType($type)->describe($level);
				}

				return implode('&', $typeNames);
			},
			function () use ($level): string {
				$typeNames = [];
				$accessoryTypes = [];
				foreach ($this->types as $type) {
					if ($type instanceof AccessoryNonEmptyStringType) {
						$accessoryTypes[] = $type;
					}
					if ($type instanceof AccessoryType && !$type instanceof AccessoryNumericStringType && !$type instanceof NonEmptyArrayType && !$type instanceof AccessoryNonEmptyStringType) {
						continue;
					}
					$typeNames[] = $type->describe($level);
				}

				if (count($accessoryTypes) === 1) {
					return $accessoryTypes[0]->describe($level);
				}

				return implode('&', $typeNames);
			},
			function () use ($level): string {
				$typeNames = [];
				$accessoryTypes = [];
				foreach ($this->types as $type) {
					if ($type instanceof AccessoryNonEmptyStringType) {
						$accessoryTypes[] = $type;
					}
					$typeNames[] = $type->describe($level);
				}

				if (count($accessoryTypes) === 1) {
					return $accessoryTypes[0]->describe($level);
				}

				return implode('&', $typeNames);
			}
		);
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->canAccessProperties();
		});
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type) use ($propertyName): TrinaryLogic {
			return $type->hasProperty($propertyName);
		});
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		$propertyPrototypes = [];
		foreach ($this->types as $type) {
			if (!$type->hasProperty($propertyName)->yes()) {
				continue;
			}

			$propertyPrototypes[] = $type->getUnresolvedPropertyPrototype($propertyName, $scope)->withFechedOnType($this);
		}

		$propertiesCount = count($propertyPrototypes);
		if ($propertiesCount === 0) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		if ($propertiesCount === 1) {
			return $propertyPrototypes[0];
		}

		return new IntersectionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->canCallMethods();
		});
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type) use ($methodName): TrinaryLogic {
			return $type->hasMethod($methodName);
		});
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		$methodPrototypes = [];
		foreach ($this->types as $type) {
			if (!$type->hasMethod($methodName)->yes()) {
				continue;
			}

			$methodPrototypes[] = $type->getUnresolvedMethodPrototype($methodName, $scope)->withCalledOnType($this);
		}

		$methodsCount = count($methodPrototypes);
		if ($methodsCount === 0) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		if ($methodsCount === 1) {
			return $methodPrototypes[0];
		}

		return new IntersectionTypeUnresolvedMethodPrototypeReflection($methodName, $methodPrototypes);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->canAccessConstants();
		});
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type) use ($constantName): TrinaryLogic {
			return $type->hasConstant($constantName);
		});
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		foreach ($this->types as $type) {
			if ($type->hasConstant($constantName)->yes()) {
				return $type->getConstant($constantName);
			}
		}

		throw new \PHPStan\ShouldNotHappenException();
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->isIterable();
		});
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->isIterableAtLeastOnce();
		});
	}

	public function getIterableKeyType(): Type
	{
		return $this->intersectTypes(static function (Type $type): Type {
			return $type->getIterableKeyType();
		});
	}

	public function getIterableValueType(): Type
	{
		return $this->intersectTypes(static function (Type $type): Type {
			return $type->getIterableValueType();
		});
	}

	public function isArray(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->isArray();
		});
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->isNumericString();
		});
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->isNonEmptyString();
		});
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->isOffsetAccessible();
		});
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type) use ($offsetType): TrinaryLogic {
			return $type->hasOffsetValueType($offsetType);
		});
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return $this->intersectTypes(static function (Type $type) use ($offsetType): Type {
			return $type->getOffsetValueType($offsetType);
		});
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this->intersectTypes(static function (Type $type) use ($offsetType, $valueType, $unionValues): Type {
			return $type->setOffsetValueType($offsetType, $valueType, $unionValues);
		});
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->isCallable();
		});
	}

	/**
	 * @param \PHPStan\Reflection\ClassMemberAccessAnswerer $scope
	 * @return \PHPStan\Reflection\ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		if ($this->isCallable()->no()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return [new TrivialParametersAcceptor()];
	}

	public function isCloneable(): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type): TrinaryLogic {
			return $type->isCloneable();
		});
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type) use ($otherType): TrinaryLogic {
			return $type->isSmallerThan($otherType);
		});
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type) use ($otherType): TrinaryLogic {
			return $type->isSmallerThanOrEqual($otherType);
		});
	}

	public function isGreaterThan(Type $otherType): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type) use ($otherType): TrinaryLogic {
			return $otherType->isSmallerThan($type);
		});
	}

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->intersectResults(static function (Type $type) use ($otherType): TrinaryLogic {
			return $otherType->isSmallerThanOrEqual($type);
		});
	}

	public function getSmallerType(): Type
	{
		return $this->intersectTypes(static function (Type $type): Type {
			return $type->getSmallerType();
		});
	}

	public function getSmallerOrEqualType(): Type
	{
		return $this->intersectTypes(static function (Type $type): Type {
			return $type->getSmallerOrEqualType();
		});
	}

	public function getGreaterType(): Type
	{
		return $this->intersectTypes(static function (Type $type): Type {
			return $type->getGreaterType();
		});
	}

	public function getGreaterOrEqualType(): Type
	{
		return $this->intersectTypes(static function (Type $type): Type {
			return $type->getGreaterOrEqualType();
		});
	}

	public function toBoolean(): BooleanType
	{
		$type = $this->intersectTypes(static function (Type $type): BooleanType {
			return $type->toBoolean();
		});

		if (!$type instanceof BooleanType) {
			return new BooleanType();
		}

		return $type;
	}

	public function toNumber(): Type
	{
		$type = $this->intersectTypes(static function (Type $type): Type {
			return $type->toNumber();
		});

		return $type;
	}

	public function toString(): Type
	{
		$type = $this->intersectTypes(static function (Type $type): Type {
			return $type->toString();
		});

		return $type;
	}

	public function toInteger(): Type
	{
		$type = $this->intersectTypes(static function (Type $type): Type {
			return $type->toInteger();
		});

		return $type;
	}

	public function toFloat(): Type
	{
		$type = $this->intersectTypes(static function (Type $type): Type {
			return $type->toFloat();
		});

		return $type;
	}

	public function toArray(): Type
	{
		$type = $this->intersectTypes(static function (Type $type): Type {
			return $type->toArray();
		});

		return $type;
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		$types = TemplateTypeMap::createEmpty();

		foreach ($this->types as $type) {
			$types = $types->intersect($type->inferTemplateTypes($receivedType));
		}

		return $types;
	}

	public function inferTemplateTypesOn(Type $templateType): TemplateTypeMap
	{
		$types = TemplateTypeMap::createEmpty();

		foreach ($this->types as $type) {
			$types = $types->intersect($templateType->inferTemplateTypes($type));
		}

		return $types;
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$references = [];

		foreach ($this->types as $type) {
			foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
				$references[] = $reference;
			}
		}

		return $references;
	}

	public function traverse(callable $cb): Type
	{
		$types = [];
		$changed = false;

		foreach ($this->types as $type) {
			$newType = $cb($type);
			if ($type !== $newType) {
				$changed = true;
			}
			$types[] = $newType;
		}

		if ($changed) {
			return TypeCombinator::intersect(...$types);
		}

		return $this;
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['types']);
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $getResult
	 * @return TrinaryLogic
	 */
	private function intersectResults(callable $getResult): TrinaryLogic
	{
		$operands = array_map($getResult, $this->types);
		return TrinaryLogic::maxMin(...$operands);
	}

	/**
	 * @param callable(Type $type): Type $getType
	 * @return Type
	 */
	private function intersectTypes(callable $getType): Type
	{
		$operands = array_map($getType, $this->types);
		return TypeCombinator::intersect(...$operands);
	}

}
