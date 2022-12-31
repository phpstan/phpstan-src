<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
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
use function ksort;
use function sprintf;
use function strlen;
use function substr;

/** @api */
class IntersectionType implements CompoundType
{

	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	private bool $sortedTypes = false;

	/**
	 * @api
	 * @param Type[] $types
	 */
	public function __construct(private array $types)
	{
		if (count($types) < 2) {
			throw new ShouldNotHappenException(sprintf(
				'Cannot create %s with: %s',
				self::class,
				implode(', ', array_map(static fn (Type $type): string => $type->describe(VerbosityLevel::value()), $types)),
			));
		}
	}

	/**
	 * @return Type[]
	 */
	public function getTypes(): array
	{
		return $this->types;
	}

	/**
	 * @return Type[]
	 */
	private function getSortedTypes(): array
	{
		if ($this->sortedTypes) {
			return $this->types;
		}

		$this->types = UnionTypeHelper::sortTypes($this->types);
		$this->sortedTypes = true;

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

	public function getReferencedClasses(): array
	{
		$classes = [];
		foreach ($this->types as $type) {
			foreach ($type->getReferencedClasses() as $className) {
				$classes[] = $className;
			}
		}

		return $classes;
	}

	public function getArrays(): array
	{
		$arrays = [];
		foreach ($this->types as $type) {
			if (!$type instanceof ArrayType) {
				continue;
			}

			foreach ($type->getArrays() as $array) {
				$arrays[] = $array;
			}
		}

		return $arrays;
	}

	public function getConstantArrays(): array
	{
		$constantArrays = [];
		foreach ($this->types as $type) {
			foreach ($type->getConstantArrays() as $constantArray) {
				$constantArrays[] = $constantArray;
			}
		}

		return $constantArrays;
	}

	public function getConstantStrings(): array
	{
		$strings = [];
		foreach ($this->types as $type) {
			foreach ($type->getConstantStrings() as $string) {
				$strings[] = $string;
			}
		}

		return $strings;
	}

	public function accepts(Type $otherType, bool $strictTypes): TrinaryLogic
	{
		$results = [];
		foreach ($this->types as $type) {
			$results[] = $type->accepts($otherType, $strictTypes);
		}

		return TrinaryLogic::createYes()->and(...$results);
	}

	public function isSuperTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof IntersectionType && $this->equals($otherType)) {
			return TrinaryLogic::createYes();
		}

		if ($otherType instanceof NeverType) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createYes()->lazyAnd($this->getTypes(), static fn (Type $innerType) => $innerType->isSuperTypeOf($otherType));
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if (($otherType instanceof self || $otherType instanceof UnionType) && !$otherType instanceof TemplateType) {
			return $otherType->isSuperTypeOf($this);
		}

		$result = TrinaryLogic::lazyMaxMin($this->getTypes(), static fn (Type $innerType) => $otherType->isSuperTypeOf($innerType));
		if ($this->isOversizedArray()->yes()) {
			if (!$result->no()) {
				return TrinaryLogic::createYes();
			}
		}

		return $result;
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		$result = TrinaryLogic::lazyMaxMin($this->getTypes(), static fn (Type $innerType) => $acceptingType->accepts($innerType, $strictTypes));
		if ($this->isOversizedArray()->yes()) {
			if (!$result->no()) {
				return TrinaryLogic::createYes();
			}
		}

		return $result;
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof static) {
			return false;
		}

		if (count($this->types) !== count($type->types)) {
			return false;
		}

		$otherTypes = $type->types;
		foreach ($this->types as $innerType) {
			$match = false;
			foreach ($otherTypes as $i => $otherType) {
				if (!$innerType->equals($otherType)) {
					continue;
				}

				$match = true;
				unset($otherTypes[$i]);
				break;
			}

			if (!$match) {
				return false;
			}
		}

		return count($otherTypes) === 0;
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			function () use ($level): string {
				$typeNames = [];
				foreach ($this->getSortedTypes() as $type) {
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
		$baseTypes = [];
		$typesToDescribe = [];
		$skipTypeNames = [];

		$nonEmptyStr = false;
		$nonFalsyStr = false;
		foreach ($this->getSortedTypes() as $i => $type) {
			if ($type instanceof AccessoryNonEmptyStringType
				|| $type instanceof AccessoryLiteralStringType
				|| $type instanceof AccessoryNumericStringType
				|| $type instanceof AccessoryNonFalsyStringType
			) {
				if ($type instanceof AccessoryNonFalsyStringType) {
					$nonFalsyStr = true;
				}
				if ($type instanceof AccessoryNonEmptyStringType) {
					$nonEmptyStr = true;
				}
				if ($nonEmptyStr && $nonFalsyStr) {
					// prevent redundant 'non-empty-string&non-falsy-string'
					foreach ($typesToDescribe as $key => $typeToDescribe) {
						if (!($typeToDescribe instanceof AccessoryNonEmptyStringType)) {
							continue;
						}

						unset($typesToDescribe[$key]);
					}
				}

				$typesToDescribe[$i] = $type;
				$skipTypeNames[] = 'string';
				continue;
			}
			if ($type instanceof NonEmptyArrayType || $type instanceof AccessoryArrayListType) {
				$typesToDescribe[$i] = $type;
				$skipTypeNames[] = 'array';
				continue;
			}

			if ($type instanceof CallableType && $type->isCommonCallable()) {
				$typesToDescribe[$i] = $type;
				$skipTypeNames[] = 'object';
				$skipTypeNames[] = 'string';
				continue;
			}

			if (!$type instanceof AccessoryType) {
				$baseTypes[$i] = $type;
				continue;
			}

			if ($skipAccessoryTypes) {
				continue;
			}

			$typesToDescribe[$i] = $type;
		}

		$describedTypes = [];
		foreach ($baseTypes as $i => $type) {
			$typeDescription = $type->describe($level);

			if (in_array($typeDescription, ['object', 'string'], true) && in_array($typeDescription, $skipTypeNames, true)) {
				foreach ($typesToDescribe as $j => $typeToDescribe) {
					if ($typeToDescribe instanceof CallableType && $typeToDescribe->isCommonCallable()) {
						$describedTypes[$i] = 'callable-' . $typeDescription;
						unset($typesToDescribe[$j]);
						continue 2;
					}
				}
			}

			if (
				substr($typeDescription, 0, strlen('array<')) === 'array<'
				&& in_array('array', $skipTypeNames, true)
			) {
				$nonEmpty = false;
				$typeName = 'array';
				foreach ($typesToDescribe as $j => $typeToDescribe) {
					if (
						$typeToDescribe instanceof AccessoryArrayListType
						&& substr($typeDescription, 0, strlen('array<int<0, max>, ')) === 'array<int<0, max>, '
					) {
						$typeName = 'list';
						$typeDescription = 'array<' . substr($typeDescription, strlen('array<int<0, max>, '));
					} elseif ($typeToDescribe instanceof NonEmptyArrayType) {
						$nonEmpty = true;
					} else {
						continue;
					}

					unset($typesToDescribe[$j]);
				}

				if ($nonEmpty) {
					$typeName = 'non-empty-' . $typeName;
				}

				$describedTypes[$i] = $typeName . '<' . substr($typeDescription, strlen('array<'));
				continue;
			}

			if (in_array($typeDescription, $skipTypeNames, true)) {
				continue;
			}

			$describedTypes[$i] = $type->describe($level);
		}

		foreach ($typesToDescribe as $i => $typeToDescribe) {
			$describedTypes[$i] = $typeToDescribe->describe($level);
		}

		ksort($describedTypes);

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

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
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

	public function getArraySize(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getArraySize());
	}

	public function getIterableKeyType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getIterableKeyType());
	}

	public function getFirstIterableKeyType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getFirstIterableKeyType());
	}

	public function getLastIterableKeyType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getLastIterableKeyType());
	}

	public function getIterableValueType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getIterableValueType());
	}

	public function getFirstIterableValueType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getFirstIterableValueType());
	}

	public function getLastIterableValueType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getLastIterableValueType());
	}

	public function isArray(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isArray());
	}

	public function isConstantArray(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isConstantArray());
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isOversizedArray());
	}

	public function isList(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isList());
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

	public function isNonFalsyString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isNonFalsyString());
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isLiteralString());
	}

	public function isClassStringType(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isClassStringType());
	}

	public function isVoid(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isVoid());
	}

	public function isScalar(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isScalar());
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

	public function getKeysArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getKeysArray());
	}

	public function getValuesArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getValuesArray());
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->fillKeysArray($valueType));
	}

	public function flipArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->flipArray());
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->intersectKeyArray($otherArraysType));
	}

	public function popArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->popArray());
	}

	public function searchArray(Type $needleType): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->searchArray($needleType));
	}

	public function shiftArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->shiftArray());
	}

	public function shuffleArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->shuffleArray());
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

	public function isNull(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isNull());
	}

	public function isTrue(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isTrue());
	}

	public function isFalse(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isFalse());
	}

	public function isBoolean(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isBoolean());
	}

	public function isFloat(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isFloat());
	}

	public function isInteger(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isInteger());
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

	public function toArrayKey(): Type
	{
		if ($this->isNumericString()->yes()) {
			return new IntegerType();
		}

		if ($this->isString()->yes()) {
			return $this;
		}

		return $this->intersectTypes(static fn (Type $type): Type => $type->toArrayKey());
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
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['types']);
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $getResult
	 */
	private function intersectResults(callable $getResult): TrinaryLogic
	{
		return TrinaryLogic::lazyMaxMin($this->types, $getResult);
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
