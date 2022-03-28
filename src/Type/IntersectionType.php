<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use function array_map;
use function count;
use function implode;
use function in_array;
use function sprintf;
use function strlen;
use function substr;

/** @api */
class IntersectionType implements CompoundType
{

	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	/** @var Type[] */
	private array $types;

	/**
	 * @api
	 * @param Type[] $types
	 */
	public function __construct(array $types)
	{
		if (count($types) < 2) {
			throw new ShouldNotHappenException(sprintf(
				'Cannot create %s with: %s',
				self::class,
				implode(', ', array_map(static fn (Type $type): string => $type->describe(VerbosityLevel::value()), $types)),
			));
		}

		$this->types = UnionTypeHelper::sortTypes($types);
	}

	/**
	 * @return Type[]
	 */
	public function getTypes(): array
	{
		return $this->types;
	}

	public function inferTemplateTypesOn(Type $templateType): TemplateTypeMap
	{
		$types = TemplateTypeMap::createEmpty();

		foreach ($this->types as $type) {
			$types = $types->intersect($templateType->inferTemplateTypes($type));
		}

		return $types;
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

		if ($otherType instanceof NeverType) {
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
		if (($otherType instanceof self || $otherType instanceof UnionType) && !$otherType instanceof TemplateType) {
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
					$typeNames[] = $type->generalize(GeneralizePrecision::lessSpecific())->describe($level);
				}

				return implode('&', $typeNames);
			},
			fn (): string => $this->describeItself($level, true),
			fn (): string => $this->describeItself($level, false),
		);
	}

	private function describeItself(VerbosityLevel $level, bool $skipAccessoryTypes): string
	{
		$typesToDescribe = [];
		$skipTypeNames = [];
		foreach ($this->types as $type) {
			if ($type instanceof AccessoryNonEmptyStringType || $type instanceof AccessoryLiteralStringType || $type instanceof AccessoryNumericStringType) {
				$typesToDescribe[] = $type;
				$skipTypeNames[] = 'string';
				continue;
			}
			if ($type instanceof NonEmptyArrayType) {
				$typesToDescribe[] = $type;
				$skipTypeNames[] = 'array';
				continue;
			}

			if ($skipAccessoryTypes) {
				continue;
			}

			if (!$type instanceof AccessoryType) {
				continue;
			}

			$typesToDescribe[] = $type;
		}

		$describedTypes = [];
		foreach ($this->types as $type) {
			if ($type instanceof AccessoryType) {
				continue;
			}
			$typeDescription = $type->describe($level);
			if (
				substr($typeDescription, 0, strlen('array<')) === 'array<'
				&& in_array('array', $skipTypeNames, true)
			) {
				foreach ($typesToDescribe as $j => $typeToDescribe) {
					if (!$typeToDescribe instanceof NonEmptyArrayType) {
						continue;
					}

					unset($typesToDescribe[$j]);
				}

				$describedTypes[] = 'non-empty-array<' . substr($typeDescription, strlen('array<'));
				continue;
			}

			if (in_array($typeDescription, $skipTypeNames, true)) {
				continue;
			}

			$describedTypes[] = $type->describe($level);
		}

		foreach ($typesToDescribe as $typeToDescribe) {
			$describedTypes[] = $typeToDescribe->describe($level);
		}

		return implode('&', $describedTypes);
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->canAccessProperties());
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->hasProperty($propertyName));
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
			throw new ShouldNotHappenException();
		}

		if ($propertiesCount === 1) {
			return $propertyPrototypes[0];
		}

		return new IntersectionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->canCallMethods());
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->hasMethod($methodName));
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
			throw new ShouldNotHappenException();
		}

		if ($methodsCount === 1) {
			return $methodPrototypes[0];
		}

		return new IntersectionTypeUnresolvedMethodPrototypeReflection($methodName, $methodPrototypes);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->canAccessConstants());
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->hasConstant($constantName));
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		foreach ($this->types as $type) {
			if ($type->hasConstant($constantName)->yes()) {
				return $type->getConstant($constantName);
			}
		}

		throw new ShouldNotHappenException();
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isIterable());
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isIterableAtLeastOnce());
	}

	public function getIterableKeyType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getIterableKeyType());
	}

	public function getIterableValueType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getIterableValueType());
	}

	public function isArray(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isArray());
	}

	public function isString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isString());
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isNumericString());
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isNonEmptyString());
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isLiteralString());
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isOffsetAccessible());
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->hasOffsetValueType($offsetType));
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getOffsetValueType($offsetType));
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->setOffsetValueType($offsetType, $valueType, $unionValues));
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->unsetOffset($offsetType));
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isCallable());
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		if ($this->isCallable()->no()) {
			throw new ShouldNotHappenException();
		}

		return [new TrivialParametersAcceptor()];
	}

	public function isCloneable(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isCloneable());
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isSmallerThan($otherType));
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isSmallerThanOrEqual($otherType));
	}

	public function isGreaterThan(Type $otherType): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $otherType->isSmallerThan($type));
	}

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $otherType->isSmallerThanOrEqual($type));
	}

	public function getSmallerType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getSmallerType());
	}

	public function getSmallerOrEqualType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getSmallerOrEqualType());
	}

	public function getGreaterType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getGreaterType());
	}

	public function getGreaterOrEqualType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getGreaterOrEqualType());
	}

	public function toBoolean(): BooleanType
	{
		$type = $this->intersectTypes(static fn (Type $type): BooleanType => $type->toBoolean());

		if (!$type instanceof BooleanType) {
			return new BooleanType();
		}

		return $type;
	}

	public function toNumber(): Type
	{
		$type = $this->intersectTypes(static fn (Type $type): Type => $type->toNumber());

		return $type;
	}

	public function toString(): Type
	{
		$type = $this->intersectTypes(static fn (Type $type): Type => $type->toString());

		return $type;
	}

	public function toInteger(): Type
	{
		$type = $this->intersectTypes(static fn (Type $type): Type => $type->toInteger());

		return $type;
	}

	public function toFloat(): Type
	{
		$type = $this->intersectTypes(static fn (Type $type): Type => $type->toFloat());

		return $type;
	}

	public function toArray(): Type
	{
		$type = $this->intersectTypes(static fn (Type $type): Type => $type->toArray());

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

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => TypeCombinator::remove($type, $typeToRemove));
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $getResult
	 */
	private function intersectResults(callable $getResult): TrinaryLogic
	{
		$operands = array_map($getResult, $this->types);
		return TrinaryLogic::maxMin(...$operands);
	}

	/**
	 * @param callable(Type $type): Type $getType
	 */
	private function intersectTypes(callable $getType): Type
	{
		$operands = array_map($getType, $this->types);
		return TypeCombinator::intersect(...$operands);
	}

}
