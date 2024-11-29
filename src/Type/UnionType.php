<?php declare(strict_types = 1);

namespace PHPStan\Type;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateUnionType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_diff_assoc;
use function array_fill_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function md5;
use function sprintf;
use function str_contains;

/** @api */
class UnionType implements CompoundType
{

	use NonGeneralizableTypeTrait;

	private bool $sortedTypes = false;

	/** @var array<int, string> */
	private array $cachedDescriptions = [];

	/**
	 * @api
	 * @param Type[] $types
	 */
	public function __construct(private array $types, private bool $normalized = false)
	{
		$throwException = static function () use ($types): void {
			throw new ShouldNotHappenException(sprintf(
				'Cannot create %s with: %s',
				self::class,
				implode(', ', array_map(static fn (Type $type): string => $type->describe(VerbosityLevel::value()), $types)),
			));
		};
		if (count($types) < 2) {
			$throwException();
		}
		foreach ($types as $type) {
			if (!($type instanceof UnionType)) {
				continue;
			}
			if ($type instanceof TemplateType) {
				continue;
			}

			$throwException();
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
	 * @param callable(Type $type): bool $filterCb
	 */
	public function filterTypes(callable $filterCb): Type
	{
		$newTypes = [];
		$changed = false;
		foreach ($this->getTypes() as $innerType) {
			if (!$filterCb($innerType)) {
				$changed = true;
				continue;
			}

			$newTypes[] = $innerType;
		}

		if (!$changed) {
			return $this;
		}

		return TypeCombinator::union(...$newTypes);
	}

	public function isNormalized(): bool
	{
		return $this->normalized;
	}

	/**
	 * @return Type[]
	 */
	protected function getSortedTypes(): array
	{
		if ($this->sortedTypes) {
			return $this->types;
		}

		$this->types = UnionTypeHelper::sortTypes($this->types);
		$this->sortedTypes = true;

		return $this->types;
	}

	/**
	 * @return string[]
	 */
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

	public function getObjectClassNames(): array
	{
		return array_values(array_unique($this->pickFromTypes(
			static fn (Type $type) => $type->getObjectClassNames(),
			static fn (Type $type) => $type->isObject()->yes(),
		)));
	}

	public function getObjectClassReflections(): array
	{
		return $this->pickFromTypes(
			static fn (Type $type) => $type->getObjectClassReflections(),
			static fn (Type $type) => $type->isObject()->yes(),
		);
	}

	public function getArrays(): array
	{
		return $this->pickFromTypes(
			static fn (Type $type) => $type->getArrays(),
			static fn (Type $type) => $type->isArray()->yes(),
		);
	}

	public function getConstantArrays(): array
	{
		return $this->pickFromTypes(
			static fn (Type $type) => $type->getConstantArrays(),
			static fn (Type $type) => $type->isArray()->yes(),
		);
	}

	public function getConstantStrings(): array
	{
		return $this->pickFromTypes(
			static fn (Type $type) => $type->getConstantStrings(),
			static fn (Type $type) => $type->isString()->yes(),
		);
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if (
			$type->equals(new ObjectType(DateTimeInterface::class))
			&& $this->accepts(
				new UnionType([new ObjectType(DateTime::class), new ObjectType(DateTimeImmutable::class)]),
				$strictTypes,
			)->yes()
		) {
			return AcceptsResult::createYes();
		}

		$result = AcceptsResult::createNo();
		foreach ($this->getSortedTypes() as $i => $innerType) {
			$result = $result->or($innerType->acceptsWithReason($type, $strictTypes)->decorateReasons(static fn (string $reason) => sprintf('Type #%d from the union: %s', $i + 1, $reason)));
		}
		if ($result->yes()) {
			return $result;
		}

		if ($type instanceof CompoundType && !$type instanceof CallableType && !$type instanceof TemplateType && !$type instanceof IntersectionType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		if ($type instanceof TemplateUnionType) {
			return $result->or($type->isAcceptedWithReasonBy($this, $strictTypes));
		}

		if ($type->isEnum()->yes() && !$this->isEnum()->no()) {
			$enumCasesUnion = TypeCombinator::union(...$type->getEnumCases());
			if (!$type->equals($enumCasesUnion)) {
				return $this->acceptsWithReason($enumCasesUnion, $strictTypes);
			}
		}

		return $result;
	}

	public function isSuperTypeOf(Type $otherType): TrinaryLogic
	{
		return $this->isSuperTypeOfWithReason($otherType)->result;
	}

	public function isSuperTypeOfWithReason(Type $otherType): IsSuperTypeOfResult
	{
		if (
			($otherType instanceof self && !$otherType instanceof TemplateUnionType)
			|| $otherType instanceof IterableType
			|| $otherType instanceof NeverType
			|| $otherType instanceof ConditionalType
			|| $otherType instanceof ConditionalTypeForParameter
			|| $otherType instanceof IntegerRangeType
		) {
			return $otherType->isSubTypeOfWithReason($this);
		}

		$results = [];
		foreach ($this->types as $innerType) {
			$result = $innerType->isSuperTypeOfWithReason($otherType);
			if ($result->yes()) {
				return $result;
			}
			$results[] = $result;
		}
		$result = IsSuperTypeOfResult::createNo()->or(...$results);

		if ($otherType instanceof TemplateUnionType) {
			return $result->or($otherType->isSubTypeOfWithReason($this));
		}

		return $result;
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		return $this->isSubTypeOfWithReason($otherType)->result;
	}

	public function isSubTypeOfWithReason(Type $otherType): IsSuperTypeOfResult
	{
		return IsSuperTypeOfResult::extremeIdentity(...array_map(static fn (Type $innerType) => $otherType->isSuperTypeOfWithReason($innerType), $this->types));
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
	}

	public function isAcceptedWithReasonBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return AcceptsResult::extremeIdentity(...array_map(static fn (Type $innerType) => $acceptingType->acceptsWithReason($innerType, $strictTypes), $this->types));
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
		if (isset($this->cachedDescriptions[$level->getLevelValue()])) {
			return $this->cachedDescriptions[$level->getLevelValue()];
		}
		$joinTypes = static function (array $types) use ($level): string {
			$typeNames = [];
			foreach ($types as $i => $type) {
				if ($type instanceof ClosureType || $type instanceof CallableType || $type instanceof TemplateUnionType) {
					$typeNames[] = sprintf('(%s)', $type->describe($level));
				} elseif ($type instanceof TemplateType) {
					$isLast = $i >= count($types) - 1;
					$bound = $type->getBound();
					if (
						!$isLast
						&& ($level->isTypeOnly() || $level->isValue())
						&& !($bound instanceof MixedType && $bound->getSubtractedType() === null && !$bound instanceof TemplateMixedType)
					) {
						$typeNames[] = sprintf('(%s)', $type->describe($level));
					} else {
						$typeNames[] = $type->describe($level);
					}
				} elseif ($type instanceof IntersectionType) {
					$intersectionDescription = $type->describe($level);
					if (str_contains($intersectionDescription, '&')) {
						$typeNames[] = sprintf('(%s)', $type->describe($level));
					} else {
						$typeNames[] = $intersectionDescription;
					}
				} else {
					$typeNames[] = $type->describe($level);
				}
			}

			if ($level->isPrecise()) {
				$duplicates = array_diff_assoc($typeNames, array_unique($typeNames));
				if (count($duplicates) > 0) {
					$indexByDuplicate = array_fill_keys($duplicates, 0);
					foreach ($typeNames as $key => $typeName) {
						if (!isset($indexByDuplicate[$typeName])) {
							continue;
						}

						$typeNames[$key] = $typeName . '#' . ++$indexByDuplicate[$typeName];
					}
				}
			} else {
				$typeNames = array_unique($typeNames);
			}

			if (count($typeNames) > 1024) {
				return implode('|', array_slice($typeNames, 0, 1024)) . "|\u{2026}";
			}

			return implode('|', $typeNames);
		};

		return $this->cachedDescriptions[$level->getLevelValue()] = $level->handle(
			function () use ($joinTypes): string {
				$types = TypeCombinator::union(...array_map(static function (Type $type): Type {
					if (
						$type->isConstantValue()->yes()
						&& $type->isTrue()->or($type->isFalse())->no()
					) {
						return $type->generalize(GeneralizePrecision::lessSpecific());
					}

					return $type;
				}, $this->getSortedTypes()));

				if ($types instanceof UnionType) {
					return $joinTypes($types->getSortedTypes());
				}

				return $joinTypes([$types]);
			},
			fn (): string => $joinTypes($this->getSortedTypes()),
		);
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $canCallback
	 * @param callable(Type $type): TrinaryLogic $hasCallback
	 */
	private function hasInternal(
		callable $canCallback,
		callable $hasCallback,
	): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->types, static function (Type $type) use ($canCallback, $hasCallback): TrinaryLogic {
			if ($canCallback($type)->no()) {
				return TrinaryLogic::createNo();
			}

			return $hasCallback($type);
		});
	}

	/**
	 * @template TObject of object
	 * @param callable(Type $type): TrinaryLogic $hasCallback
	 * @param callable(Type $type): TObject $getCallback
	 * @return TObject
	 */
	private function getInternal(
		callable $hasCallback,
		callable $getCallback,
	): object
	{
		/** @var TrinaryLogic|null $result */
		$result = null;

		/** @var TObject|null $object */
		$object = null;
		foreach ($this->types as $type) {
			$has = $hasCallback($type);
			if (!$has->yes()) {
				continue;
			}
			if ($result !== null && $result->compareTo($has) !== $has) {
				continue;
			}

			$get = $getCallback($type);
			$result = $has;
			$object = $get;
		}

		if ($object === null) {
			throw new ShouldNotHappenException();
		}

		return $object;
	}

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getTemplateType($ancestorClassName, $templateTypeName));
	}

	public function isObject(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isObject());
	}

	public function isEnum(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isEnum());
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->canAccessProperties());
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->hasProperty($propertyName));
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

		return new UnionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->canCallMethods());
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->hasMethod($methodName));
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

		return new UnionTypeUnresolvedMethodPrototypeReflection($methodName, $methodPrototypes);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->canAccessConstants());
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->hasInternal(
			static fn (Type $type): TrinaryLogic => $type->canAccessConstants(),
			static fn (Type $type): TrinaryLogic => $type->hasConstant($constantName),
		);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return $this->getInternal(
			static fn (Type $type): TrinaryLogic => $type->hasConstant($constantName),
			static fn (Type $type): ConstantReflection => $type->getConstant($constantName),
		);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isIterable());
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isIterableAtLeastOnce());
	}

	public function getArraySize(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getArraySize());
	}

	public function getIterableKeyType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getIterableKeyType());
	}

	public function getFirstIterableKeyType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getFirstIterableKeyType());
	}

	public function getLastIterableKeyType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getLastIterableKeyType());
	}

	public function getIterableValueType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getIterableValueType());
	}

	public function getFirstIterableValueType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getFirstIterableValueType());
	}

	public function getLastIterableValueType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getLastIterableValueType());
	}

	public function isArray(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isArray());
	}

	public function isConstantArray(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isConstantArray());
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isOversizedArray());
	}

	public function isList(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isList());
	}

	public function isString(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isString());
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isNumericString());
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isNonEmptyString());
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isNonFalsyString());
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isLiteralString());
	}

	public function isLowercaseString(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isLowercaseString());
	}

	public function isUppercaseString(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isUppercaseString());
	}

	public function isClassStringType(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isClassStringType());
	}

	public function getClassStringObjectType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getClassStringObjectType());
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getObjectTypeOrClassStringObjectType());
	}

	public function isVoid(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isVoid());
	}

	public function isScalar(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isScalar());
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return new BooleanType();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isOffsetAccessible());
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isOffsetAccessLegal());
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->hasOffsetValueType($offsetType));
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		$types = [];
		foreach ($this->types as $innerType) {
			$valueType = $innerType->getOffsetValueType($offsetType);
			if ($valueType instanceof ErrorType) {
				continue;
			}

			$types[] = $valueType;
		}

		if (count($types) === 0) {
			return new ErrorType();
		}

		return TypeCombinator::union(...$types);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->setOffsetValueType($offsetType, $valueType, $unionValues));
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->setExistingOffsetValueType($offsetType, $valueType));
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->unsetOffset($offsetType));
	}

	public function getKeysArray(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getKeysArray());
	}

	public function getValuesArray(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getValuesArray());
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->chunkArray($lengthType, $preserveKeys));
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->fillKeysArray($valueType));
	}

	public function flipArray(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->flipArray());
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->intersectKeyArray($otherArraysType));
	}

	public function popArray(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->popArray());
	}

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->reverseArray($preserveKeys));
	}

	public function searchArray(Type $needleType): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->searchArray($needleType));
	}

	public function shiftArray(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->shiftArray());
	}

	public function shuffleArray(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->shuffleArray());
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->sliceArray($offsetType, $lengthType, $preserveKeys));
	}

	public function getEnumCases(): array
	{
		return $this->pickFromTypes(
			static fn (Type $type) => $type->getEnumCases(),
			static fn (Type $type) => $type->isObject()->yes(),
		);
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isCallable());
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		$acceptors = [];

		foreach ($this->types as $type) {
			if ($type->isCallable()->no()) {
				continue;
			}

			$acceptors = array_merge($acceptors, $type->getCallableParametersAcceptors($scope));
		}

		if (count($acceptors) === 0) {
			throw new ShouldNotHappenException();
		}

		return $acceptors;
	}

	public function isCloneable(): TrinaryLogic
	{
		return $this->unionResults(static fn (Type $type): TrinaryLogic => $type->isCloneable());
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isSmallerThan($otherType));
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isSmallerThanOrEqual($otherType));
	}

	public function isNull(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isNull());
	}

	public function isConstantValue(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isConstantValue());
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isConstantScalarValue());
	}

	public function getConstantScalarTypes(): array
	{
		return $this->notBenevolentPickFromTypes(static fn (Type $type) => $type->getConstantScalarTypes());
	}

	public function getConstantScalarValues(): array
	{
		return $this->notBenevolentPickFromTypes(static fn (Type $type) => $type->getConstantScalarValues());
	}

	public function isTrue(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isTrue());
	}

	public function isFalse(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isFalse());
	}

	public function isBoolean(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isBoolean());
	}

	public function isFloat(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isFloat());
	}

	public function isInteger(): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $type->isInteger());
	}

	public function getSmallerType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getSmallerType());
	}

	public function getSmallerOrEqualType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getSmallerOrEqualType());
	}

	public function getGreaterType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getGreaterType());
	}

	public function getGreaterOrEqualType(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->getGreaterOrEqualType());
	}

	public function isGreaterThan(Type $otherType): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $otherType->isSmallerThan($type));
	}

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->notBenevolentUnionResults(static fn (Type $type): TrinaryLogic => $otherType->isSmallerThanOrEqual($type));
	}

	public function toBoolean(): BooleanType
	{
		/** @var BooleanType $type */
		$type = $this->unionTypes(static fn (Type $type): BooleanType => $type->toBoolean());

		return $type;
	}

	public function toNumber(): Type
	{
		$type = $this->unionTypes(static fn (Type $type): Type => $type->toNumber());

		return $type;
	}

	public function toAbsoluteNumber(): Type
	{
		$type = $this->unionTypes(static fn (Type $type): Type => $type->toAbsoluteNumber());

		return $type;
	}

	public function toString(): Type
	{
		$type = $this->unionTypes(static fn (Type $type): Type => $type->toString());

		return $type;
	}

	public function toInteger(): Type
	{
		$type = $this->unionTypes(static fn (Type $type): Type => $type->toInteger());

		return $type;
	}

	public function toFloat(): Type
	{
		$type = $this->unionTypes(static fn (Type $type): Type => $type->toFloat());

		return $type;
	}

	public function toArray(): Type
	{
		$type = $this->unionTypes(static fn (Type $type): Type => $type->toArray());

		return $type;
	}

	public function toArrayKey(): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->toArrayKey());
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		$types = TemplateTypeMap::createEmpty();
		if ($receivedType instanceof UnionType) {
			$myTypes = [];
			$remainingReceivedTypes = [];
			foreach ($receivedType->getTypes() as $receivedInnerType) {
				foreach ($this->types as $type) {
					if ($type->isSuperTypeOf($receivedInnerType)->yes()) {
						$types = $types->union($type->inferTemplateTypes($receivedInnerType));
						continue 2;
					}
					$myTypes[] = $type;
				}
				$remainingReceivedTypes[] = $receivedInnerType;
			}
			if (count($remainingReceivedTypes) === 0) {
				return $types;
			}
			$receivedType = TypeCombinator::union(...$remainingReceivedTypes);
		} else {
			$myTypes = $this->types;
		}

		foreach ($myTypes as $type) {
			if ($type instanceof TemplateType || ($type instanceof GenericClassStringType && $type->getGenericType() instanceof TemplateType)) {
				continue;
			}
			$types = $types->union($type->inferTemplateTypes($receivedType));
		}

		if (!$types->isEmpty()) {
			return $types;
		}

		foreach ($myTypes as $type) {
			$types = $types->union($type->inferTemplateTypes($receivedType));
		}

		return $types;
	}

	public function inferTemplateTypesOn(Type $templateType): TemplateTypeMap
	{
		$types = TemplateTypeMap::createEmpty();

		foreach ($this->types as $type) {
			$types = $types->union($templateType->inferTemplateTypes($type));
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
			return TypeCombinator::union(...$types);
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		$types = [];
		$changed = false;

		if (!$right instanceof self) {
			return $this;
		}

		if (count($this->getTypes()) !== count($right->getTypes())) {
			return $this;
		}

		foreach ($this->getSortedTypes() as $i => $leftType) {
			$rightType = $right->getSortedTypes()[$i];
			$newType = $cb($leftType, $rightType);
			if ($leftType !== $newType) {
				$changed = true;
			}
			$types[] = $newType;
		}

		if ($changed) {
			return TypeCombinator::union(...$types);
		}

		return $this;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return $this->unionTypes(static fn (Type $type): Type => TypeCombinator::remove($type, $typeToRemove));
	}

	public function exponentiate(Type $exponent): Type
	{
		return $this->unionTypes(static fn (Type $type): Type => $type->exponentiate($exponent));
	}

	public function getFiniteTypes(): array
	{
		$types = $this->notBenevolentPickFromTypes(static fn (Type $type) => $type->getFiniteTypes());
		$uniquedTypes = [];
		foreach ($types as $type) {
			$uniquedTypes[md5($type->describe(VerbosityLevel::cache()))] = $type;
		}

		if (count($uniquedTypes) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return [];
		}

		return array_values($uniquedTypes);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['types'], $properties['normalized']);
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $getResult
	 */
	protected function unionResults(callable $getResult): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->types, $getResult);
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $getResult
	 */
	private function notBenevolentUnionResults(callable $getResult): TrinaryLogic
	{
		return TrinaryLogic::lazyExtremeIdentity($this->types, $getResult);
	}

	/**
	 * @param callable(Type $type): Type $getType
	 */
	protected function unionTypes(callable $getType): Type
	{
		return TypeCombinator::union(...array_map($getType, $this->types));
	}

	/**
	 * @template T of Type
	 * @param callable(Type $type): list<T> $getTypes
	 * @return list<T>
	 *
	 * @deprecated Use pickFromTypes() instead.
	 */
	protected function pickTypes(callable $getTypes): array
	{
		return $this->pickFromTypes($getTypes, static fn () => false);
	}

	/**
	 * @template T
	 * @param callable(Type $type): list<T> $getValues
	 * @param callable(Type $type): bool $criteria
	 * @return list<T>
	 */
	protected function pickFromTypes(
		callable $getValues,
		callable $criteria,
	): array
	{
		$values = [];
		foreach ($this->types as $type) {
			$innerValues = $getValues($type);
			if ($innerValues === []) {
				return [];
			}

			foreach ($innerValues as $innerType) {
				$values[] = $innerType;
			}
		}

		return $values;
	}

	public function toPhpDocNode(): TypeNode
	{
		return new UnionTypeNode(array_map(static fn (Type $type) => $type->toPhpDocNode(), $this->getSortedTypes()));
	}

	/**
	 * @template T
	 * @param callable(Type $type): list<T> $getValues
	 * @return list<T>
	 */
	private function notBenevolentPickFromTypes(callable $getValues): array
	{
		$values = [];
		foreach ($this->types as $type) {
			$innerValues = $getValues($type);
			if ($innerValues === []) {
				return [];
			}

			foreach ($innerValues as $innerType) {
				$values[] = $innerType;
			}
		}

		return $values;
	}

}
