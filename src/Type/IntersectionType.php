<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Internal\CombinationsHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MissingConstantFromReflectionException;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\TemplateArrayType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use function array_filter;
use function array_intersect_key;
use function array_map;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_int;
use function ksort;
use function sprintf;
use function str_starts_with;
use function strcasecmp;
use function strlen;
use function substr;
use function usort;

/** @api */
class IntersectionType implements CompoundType
{

	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	private bool $sortedTypes = false;

	private ?TrinaryLogic $isBoolean = null;

	private ?TrinaryLogic $isFloat = null;

	private ?TrinaryLogic $isInteger = null;

	private ?TrinaryLogic $isString = null;

	private ?TrinaryLogic $isArray = null;

	private ?TrinaryLogic $isList = null;

	private ?TrinaryLogic $isConstantArray = null;

	private ?TrinaryLogic $isOversizedArray = null;

	private ?TrinaryLogic $isOffsetAccessible = null;

	private ?TrinaryLogic $isIterableAtLeastOnce = null;

	private ?TrinaryLogic $isConstantScalarValue = null;

	private ?TrinaryLogic $isCallable = null;

	/** @var array<string, Type> */
	private array $cachedGetOffsetValueType = [];

	/** @var array<string, TrinaryLogic> */
	private array $cachedHasOffsetValueType = [];

	/**
	 * @api
	 * @param list<Type> $types
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
	 * @return list<Type>
	 */
	public function getTypes(): array
	{
		return $this->types;
	}

	/**
	 * @return list<Type>
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

	public function getObjectClassNames(): array
	{
		$objectClassNames = [];
		foreach ($this->types as $type) {
			$innerObjectClassNames = $type->getObjectClassNames();
			foreach ($innerObjectClassNames as $innerObjectClassName) {
				$objectClassNames[] = $innerObjectClassName;
			}
		}

		return array_values(array_unique($objectClassNames));
	}

	public function getObjectClassReflections(): array
	{
		$reflections = [];
		foreach ($this->types as $type) {
			foreach ($type->getObjectClassReflections() as $reflection) {
				$reflections[] = $reflection;
			}
		}

		return $reflections;
	}

	public function getArrays(): array
	{
		$arrays = [];
		foreach ($this->types as $type) {
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

	public function accepts(Type $otherType, bool $strictTypes): AcceptsResult
	{
		$result = AcceptsResult::createYes();
		foreach ($this->types as $type) {
			$result = $result->and($type->accepts($otherType, $strictTypes));
		}

		if (!$result->yes()) {
			$isList = $otherType->isList();
			$reasons = $result->reasons;
			$verbosity = VerbosityLevel::getRecommendedLevelByType($this, $otherType);
			if ($this->isList()->yes() && !$isList->yes()) {
				$reasons[] = sprintf(
					'%s %s a list.',
					$otherType->describe($verbosity),
					$isList->no() ? 'is not' : 'might not be',
				);
			}

			$isNonEmpty = $otherType->isIterableAtLeastOnce();
			if ($this->isIterableAtLeastOnce()->yes() && !$isNonEmpty->yes()) {
				$reasons[] = sprintf(
					'%s %s empty.',
					$otherType->describe($verbosity),
					$isNonEmpty->no() ? 'is' : 'might be',
				);
			}

			if (count($reasons) > 0) {
				return new AcceptsResult($result->result, $reasons);
			}
		}

		return $result;
	}

	public function isSuperTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if ($otherType instanceof IntersectionType && $this->equals($otherType)) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($otherType instanceof NeverType) {
			return IsSuperTypeOfResult::createYes();
		}

		return IsSuperTypeOfResult::createYes()->and(...array_map(static fn (Type $innerType) => $innerType->isSuperTypeOf($otherType), $this->types));
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if (($otherType instanceof self || $otherType instanceof UnionType) && !$otherType instanceof TemplateType) {
			return $otherType->isSuperTypeOf($this);
		}

		$result = IsSuperTypeOfResult::lazyMaxMin(
			$this->types,
			static fn (Type $innerType) => $otherType->isSuperTypeOf($innerType),
		);

		if (
			!$result->no()
			&& $this->isOversizedArray()->yes()
			&& !$otherType->isIterableAtLeastOnce()->no()
		) {
			return IsSuperTypeOfResult::createYes();
		}

		return $result;
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		$result = AcceptsResult::lazyMaxMin(
			$this->types,
			static fn (Type $innerType) => $acceptingType->accepts($innerType, $strictTypes),
		);

		if ($this->isOversizedArray()->yes()) {
			if (!$result->no()) {
				return AcceptsResult::createYes();
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
			fn (): string => $this->describeType($level),
			fn (): string => $this->describeItself($level, true),
			fn (): string => $this->describeItself($level, false),
		);
	}

	private function describeType(VerbosityLevel $level): string
	{
		$typeNames = [];
		$isList = $this->isList()->yes();
		$valueType = null;
		foreach ($this->getSortedTypes() as $type) {
			if ($isList) {
				if ($type instanceof ArrayType || $type instanceof ConstantArrayType) {
					$valueType = $type->getIterableValueType();
					continue;
				}
				if ($type instanceof NonEmptyArrayType) {
					continue;
				}
			}
			if ($type instanceof AccessoryType) {
				continue;
			}
			$typeNames[] = $type->generalize(GeneralizePrecision::lessSpecific())->describe($level);
		}

		if ($isList) {
			$isMixedValueType = $valueType instanceof MixedType && $valueType->describe(VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
			$innerType = '';
			if ($valueType !== null && !$isMixedValueType) {
				$innerType = sprintf('<%s>', $valueType->describe($level));
			}

			$typeNames[] = 'list' . $innerType;
		}

		usort($typeNames, static function ($a, $b) {
			$cmp = strcasecmp($a, $b);
			if ($cmp !== 0) {
				return $cmp;
			}

			return $a <=> $b;
		});

		return implode('&', $typeNames);
	}

	private function describeItself(VerbosityLevel $level, bool $skipAccessoryTypes): string
	{
		$baseTypes = [];
		$typesToDescribe = [];
		$skipTypeNames = [];

		$nonEmptyStr = false;
		$nonFalsyStr = false;
		$isList = $this->isList()->yes();
		$isArray = $this->isArray()->yes();
		$isNonEmptyArray = $this->isIterableAtLeastOnce()->yes();
		// When a TemplateArrayType carries the array refinement, we describe
		// it via its own describe() (e.g. "T of array") rather than collapsing
		// it into a generic `array<...>` prefix. In that case the
		// `NonEmptyArrayType` and `AccessoryArrayListType` markers must
		// describe themselves explicitly — they cannot be absorbed into a
		// non-existent `non-empty-array` prefix.
		$hasTemplateArray = false;
		if ($isArray || $isList) {
			foreach ($this->types as $type) {
				if ($type instanceof TemplateArrayType) {
					$hasTemplateArray = true;
					break;
				}
			}
		}
		$describedTypes = [];
		foreach ($this->getSortedTypes() as $i => $type) {
			if ($type instanceof AccessoryNonEmptyStringType
				|| $type instanceof AccessoryLiteralStringType
				|| $type instanceof AccessoryNumericStringType
				|| $type instanceof AccessoryNonFalsyStringType
				|| $type instanceof AccessoryLowercaseStringType
				|| $type instanceof AccessoryUppercaseStringType
			) {
				if (
					($type instanceof AccessoryLowercaseStringType || $type instanceof AccessoryUppercaseStringType)
					&& !$level->isPrecise()
					&& !$level->isCache()
				) {
					continue;
				}
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
			if ($isList || $isArray) {
				if ($type instanceof TemplateArrayType) {
					// Preserve the template's own describe (e.g. "T of array")
					// instead of collapsing it to a generic array shape — the
					// other intersection members already carry the array
					// refinement.
					$describedTypes[$i] = $type->describe($level);
					continue;
				}
				if ($type instanceof ArrayType) {
					$keyType = $type->getKeyType();
					$valueType = $type->getItemType();
					if ($isList) {
						$isMixedValueType = $valueType instanceof MixedType && $valueType->describe(VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
						$valueTypeDescription = '';
						if (!$isMixedValueType) {
							$valueTypeDescription = sprintf('<%s>', $valueType->describe($level));
						}

						$describedTypes[$i] = ($isNonEmptyArray ? 'non-empty-list' : 'list') . $valueTypeDescription;
					} else {
						$isMixedKeyType = $keyType instanceof MixedType && $keyType->describe(VerbosityLevel::precise()) === 'mixed' && !$keyType->isExplicitMixed();
						$isMixedValueType = $valueType instanceof MixedType && $valueType->describe(VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
						$typeDescription = '';
						if (!$isMixedKeyType) {
							$typeDescription = sprintf('<%s, %s>', $keyType->describe($level), $valueType->describe($level));
						} elseif (!$isMixedValueType) {
							$typeDescription = sprintf('<%s>', $valueType->describe($level));
						}

						$describedTypes[$i] = ($isNonEmptyArray ? 'non-empty-array' : 'array') . $typeDescription;
					}
					continue;
				} elseif ($type instanceof ConstantArrayType) {
					$description = $type->describe($level);
					$kind = str_starts_with($description, 'list') ? 'list' : 'array';
					$descriptionWithoutKind = substr($description, strlen($kind));
					$begin = $isList ? 'list' : 'array';
					if ($isNonEmptyArray && !$type->isIterableAtLeastOnce()->yes()) {
						$begin = 'non-empty-' . $begin;
					}

					$describedTypes[$i] = $begin . $descriptionWithoutKind;
					continue;
				}
				if ($type instanceof NonEmptyArrayType || $type instanceof AccessoryArrayListType) {
					if ($hasTemplateArray) {
						$describedTypes[$i] = $type->describe($level);
					}
					continue;
				}
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

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getTemplateType($ancestorClassName, $templateTypeName));
	}

	public function isObject(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isObject());
	}

	public function getClassStringType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getClassStringType());
	}

	public function isEnum(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isEnum());
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->canAccessProperties());
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->hasProperty($propertyName));
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
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
			throw new MissingPropertyFromReflectionException($this->describe(VerbosityLevel::typeOnly()), $propertyName);
		}

		if ($propertiesCount === 1) {
			return $propertyPrototypes[0];
		}

		return new IntersectionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
	}

	public function hasInstanceProperty(string $propertyName): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->hasInstanceProperty($propertyName));
	}

	public function getInstanceProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->getUnresolvedInstancePropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		$propertyPrototypes = [];
		foreach ($this->types as $type) {
			if (!$type->hasInstanceProperty($propertyName)->yes()) {
				continue;
			}

			$propertyPrototypes[] = $type->getUnresolvedInstancePropertyPrototype($propertyName, $scope)->withFechedOnType($this);
		}

		$propertiesCount = count($propertyPrototypes);
		if ($propertiesCount === 0) {
			throw new MissingPropertyFromReflectionException($this->describe(VerbosityLevel::typeOnly()), $propertyName);
		}

		if ($propertiesCount === 1) {
			return $propertyPrototypes[0];
		}

		return new IntersectionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
	}

	public function hasStaticProperty(string $propertyName): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->hasStaticProperty($propertyName));
	}

	public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->getUnresolvedStaticPropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		$propertyPrototypes = [];
		foreach ($this->types as $type) {
			if (!$type->hasStaticProperty($propertyName)->yes()) {
				continue;
			}

			$propertyPrototypes[] = $type->getUnresolvedStaticPropertyPrototype($propertyName, $scope)->withFechedOnType($this);
		}

		$propertiesCount = count($propertyPrototypes);
		if ($propertiesCount === 0) {
			throw new MissingPropertyFromReflectionException($this->describe(VerbosityLevel::typeOnly()), $propertyName);
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
			throw new MissingMethodFromReflectionException($this->describe(VerbosityLevel::typeOnly()), $methodName);
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

	public function getConstant(string $constantName): ClassConstantReflection
	{
		foreach ($this->types as $type) {
			if ($type->hasConstant($constantName)->yes()) {
				return $type->getConstant($constantName);
			}
		}

		throw new MissingConstantFromReflectionException($this->describe(VerbosityLevel::typeOnly()), $constantName);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isIterable());
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->isIterableAtLeastOnce ??= $this->intersectResults(
			static fn (Type $type): TrinaryLogic => $type->isIterableAtLeastOnce(),
			static fn (Type $type): bool => !$type->isIterable()->no(),
		);
	}

	public function getArraySize(): Type
	{
		$arraySize = $this->intersectTypes(static fn (Type $type): Type => $type->getArraySize());

		$knownOffsets = [];
		foreach ($this->types as $type) {
			if (!($type instanceof HasOffsetValueType) && !($type instanceof HasOffsetType)) {
				continue;
			}

			$knownOffsets[$type->getOffsetType()->getValue()] = true;
		}

		if ($this->isList()->yes() && $this->isIterableAtLeastOnce()->yes()) {
			$knownOffsets[0] = true;
		}

		if ($knownOffsets !== []) {
			return TypeCombinator::intersect($arraySize, IntegerRangeType::fromInterval(count($knownOffsets), null));
		}

		return $arraySize;
	}

	public function getIterableKeyType(): Type
	{
		if ($this->isCallable()->yes() && $this->isArray()->yes()) {
			return new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]);
		}
		return $this->intersectTypes(static fn (Type $type): Type => $type->getIterableKeyType());
	}

	public function getFirstIterableKeyType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getIterableKeyType());
	}

	public function getLastIterableKeyType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getIterableKeyType());
	}

	public function getIterableValueType(): Type
	{
		$result = $this->intersectTypes(static fn (Type $type): Type => $type->getIterableValueType());
		if ($this->isCallable()->yes() && $this->isArray()->yes()) {
			return TypeCombinator::intersect($result, new UnionType([new ObjectWithoutClassType(), new StringType()]));
		}
		return $result;
	}

	public function getFirstIterableValueType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getIterableValueType());
	}

	public function getLastIterableValueType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getIterableValueType());
	}

	public function isArray(): TrinaryLogic
	{
		return $this->isArray ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isArray());
	}

	public function isConstantArray(): TrinaryLogic
	{
		return $this->isConstantArray ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isConstantArray());
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return $this->isOversizedArray ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isOversizedArray());
	}

	public function isList(): TrinaryLogic
	{
		return $this->isList ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isList());
	}

	public function isString(): TrinaryLogic
	{
		return $this->isString ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isString());
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isNumericString());
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		if ($this->isCallable()->yes() && $this->isString()->yes()) {
			return TrinaryLogic::createYes();
		}
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

	public function isLowercaseString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isLowercaseString());
	}

	public function isUppercaseString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isUppercaseString());
	}

	public function isClassString(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isClassString());
	}

	public function getClassStringObjectType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getClassStringObjectType());
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getObjectTypeOrClassStringObjectType());
	}

	public function isVoid(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isScalar(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isScalar());
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return $this->intersectResults(
			static fn (Type $innerType): TrinaryLogic => $innerType->looseCompare($type, $phpVersion)->toTrinaryLogic(),
		)->toBooleanType();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->isOffsetAccessible ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isOffsetAccessible());
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isOffsetAccessLegal());
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		$cacheKey = $offsetType->describe(VerbosityLevel::cache());
		if (isset($this->cachedHasOffsetValueType[$cacheKey])) {
			return $this->cachedHasOffsetValueType[$cacheKey];
		}
		return $this->cachedHasOffsetValueType[$cacheKey] = $this->doHasOffsetValueType($offsetType);
	}

	private function doHasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		if ($this->isList()->yes()) {
			$arrayKeyOffsetType = $offsetType->toArrayKey();

			$negative = IntegerRangeType::fromInterval(null, -1);
			if ($negative->isSuperTypeOf($arrayKeyOffsetType)->yes()) {
				return TrinaryLogic::createNo();
			}

			$size = $this->getArraySize();
			if ($size instanceof IntegerRangeType && $size->getMin() !== null) {
				$knownOffsets = IntegerRangeType::fromInterval(0, $size->getMin() - 1);
			} elseif ($size instanceof ConstantIntegerType) {
				$knownOffsets = IntegerRangeType::fromInterval(0, $size->getValue() - 1);
			} elseif ($this->isIterableAtLeastOnce()->yes()) {
				$knownOffsets = new ConstantIntegerType(0);
			} else {
				$knownOffsets = null;
			}

			if ($knownOffsets !== null && $knownOffsets->isSuperTypeOf($arrayKeyOffsetType)->yes()) {
				return TrinaryLogic::createYes();
			}

			foreach ($this->types as $type) {
				if (!$type instanceof HasOffsetValueType && !$type instanceof HasOffsetType) {
					continue;
				}

				foreach ($type->getOffsetType()->getConstantScalarValues() as $constantScalarValue) {
					if (!is_int($constantScalarValue)) {
						continue;
					}
					if (IntegerRangeType::fromInterval(0, $constantScalarValue)->isSuperTypeOf($arrayKeyOffsetType)->yes()) {
						return TrinaryLogic::createYes();
					}
				}
			}
		}

		$result = $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->hasOffsetValueType($offsetType));

		if (!$result->yes() && $this->isCallable()->yes() && $this->isArray()->yes()) {
			$arrayKeyOffsetType = $offsetType->toArrayKey();
			$callableArrayOffsetType = new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]);
			if ($callableArrayOffsetType->isSuperTypeOf($arrayKeyOffsetType)->yes()) {
				return TrinaryLogic::createYes();
			}
		}

		return $result;
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		$cacheKey = $offsetType->describe(VerbosityLevel::cache());
		if (isset($this->cachedGetOffsetValueType[$cacheKey])) {
			return $this->cachedGetOffsetValueType[$cacheKey];
		}
		return $this->cachedGetOffsetValueType[$cacheKey] = $this->doGetOffsetValueType($offsetType);
	}

	private function doGetOffsetValueType(Type $offsetType): Type
	{
		$result = $this->intersectTypes(static fn (Type $type): Type => $type->getOffsetValueType($offsetType));
		if ($this->isOversizedArray()->yes()) {
			return TypeUtils::toBenevolentUnion($result);
		}

		if ($this->isCallable()->yes() && $this->isArray()->yes()) {
			$arrayKeyOffsetType = $offsetType->toArrayKey();
			$callableArrayOffsetType = new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]);
			if ($callableArrayOffsetType->isSuperTypeOf($arrayKeyOffsetType)->yes()) {
				if ((new ConstantIntegerType(0))->isSuperTypeOf($arrayKeyOffsetType)->yes()) {
					$narrowedType = new UnionType([new ClassStringType(), new ObjectWithoutClassType()]);
				} elseif ((new ConstantIntegerType(1))->isSuperTypeOf($arrayKeyOffsetType)->yes()) {
					$narrowedType = new StringType();
				} else {
					$narrowedType = new UnionType([new StringType(), new ObjectWithoutClassType()]);
				}
				$result = TypeCombinator::intersect($result, $narrowedType);
			}
		}

		return $result;
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		if ($this->isOversizedArray()->yes()) {
			return $this->intersectTypes(static function (Type $type) use ($offsetType, $valueType, $unionValues): Type {
				// avoid new HasOffsetValueType being intersected with oversized array
				if (!$type instanceof ArrayType) {
					return $type->setOffsetValueType($offsetType, $valueType, $unionValues);
				}

				if (!$offsetType instanceof ConstantStringType && !$offsetType instanceof ConstantIntegerType) {
					return $type->setOffsetValueType($offsetType, $valueType, $unionValues);
				}

				if (!$offsetType->isSuperTypeOf($type->getKeyType())->yes()) {
					return $type->setOffsetValueType($offsetType, $valueType, $unionValues);
				}

				return new IntersectionType([
					new ArrayType(
						TypeCombinator::union($type->getKeyType(), $offsetType),
						TypeCombinator::union($type->getItemType(), $valueType),
					),
					new NonEmptyArrayType(),
				]);
			});
		}

		$result = $this->intersectTypes(static fn (Type $type): Type => $type->setOffsetValueType($offsetType, $valueType, $unionValues));

		if (
			$offsetType !== null
			&& $this->isList()->yes()
			&& !$result->isList()->yes()
		) {
			if ($this->isIterableAtLeastOnce()->yes() && (new ConstantIntegerType(1))->isSuperTypeOf($offsetType)->yes()) {
				$result = TypeCombinator::intersect($result, new AccessoryArrayListType());
			} else {
				foreach ($this->types as $type) {
					if (!$type instanceof HasOffsetValueType && !$type instanceof HasOffsetType) {
						continue;
					}

					foreach ($type->getOffsetType()->getConstantScalarValues() as $constantScalarValue) {
						if (!is_int($constantScalarValue)) {
							continue;
						}
						if (IntegerRangeType::fromInterval(0, $constantScalarValue + 1)->isSuperTypeOf($offsetType)->yes()) {
							$result = TypeCombinator::intersect($result, new AccessoryArrayListType());
							break 2;
						}
					}
				}
			}
		}

		if (
			$this->isList()->yes()
			&& $offsetType !== null
			&& $offsetType->toArrayKey()->isInteger()->yes()
			&& $this->getIterableValueType()->isArray()->yes()
		) {
			$result = TypeCombinator::intersect($result, new AccessoryArrayListType());
		}

		return $result;
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->setExistingOffsetValueType($offsetType, $valueType));
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->unsetOffset($offsetType));
	}

	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getKeysArrayFiltered($filterValueType, $strict));
	}

	public function getKeysArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getKeysArray());
	}

	public function getValuesArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getValuesArray());
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->chunkArray($lengthType, $preserveKeys));
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

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->reverseArray($preserveKeys));
	}

	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->searchArray($needleType, $strict));
	}

	public function shiftArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->shiftArray());
	}

	public function shuffleArray(): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->shuffleArray());
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		$result = $this->intersectTypes(static fn (Type $type): Type => $type->sliceArray($offsetType, $lengthType, $preserveKeys));

		if (
			$this->isList()->yes()
			&& $this->isIterableAtLeastOnce()->yes()
			&& (new ConstantIntegerType(0))->isSuperTypeOf($offsetType)->yes()
			&& IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($lengthType)->yes()
		) {
			$result = TypeCombinator::intersect($result, new NonEmptyArrayType());
		}

		return $result;
	}

	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->spliceArray($offsetType, $lengthType, $replacementType));
	}

	public function getEnumCases(): array
	{
		$compare = [];
		foreach ($this->types as $type) {
			$oneType = [];
			foreach ($type->getEnumCases() as $enumCase) {
				$oneType[$enumCase->getClassName() . '::' . $enumCase->getEnumCaseName()] = $enumCase;
			}
			$compare[] = $oneType;
		}

		return array_values(array_intersect_key(...$compare));
	}

	public function getEnumCaseObject(): ?EnumCaseObjectType
	{
		$singleCase = null;
		foreach ($this->types as $type) {
			$caseObject = $type->getEnumCaseObject();
			if ($caseObject === null) {
				continue;
			}

			if ($singleCase !== null) {
				return null;
			}

			$singleCase = $caseObject;
		}

		return $singleCase;
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->isCallable ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isCallable());
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		$yesAcceptors = [];

		foreach ($this->types as $type) {
			if (!$type->isCallable()->yes()) {
				continue;
			}
			$yesAcceptors[] = $type->getCallableParametersAcceptors($scope);
		}

		if (count($yesAcceptors) === 0) {
			if ($this->isCallable()->no()) {
				throw new ShouldNotHappenException();
			}

			return [new TrivialParametersAcceptor()];
		}

		$result = [];
		$combinations = CombinationsHelper::combinations($yesAcceptors);
		foreach ($combinations as $combination) {
			$combined = ParametersAcceptorSelector::combineAcceptors($combination);
			if (!$combined instanceof CallableParametersAcceptor) {
				throw new ShouldNotHappenException();
			}
			$result[] = $combined;
		}

		return $result;
	}

	public function isCloneable(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isCloneable());
	}

	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isSmallerThan($otherType, $phpVersion));
	}

	public function isSmallerThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isSmallerThanOrEqual($otherType, $phpVersion));
	}

	public function isNull(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isNull());
	}

	public function isConstantValue(): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isConstantValue());
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return $this->isConstantScalarValue ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isConstantScalarValue());
	}

	public function getConstantScalarTypes(): array
	{
		$scalarTypes = [];
		foreach ($this->types as $type) {
			foreach ($type->getConstantScalarTypes() as $scalarType) {
				$scalarTypes[] = $scalarType;
			}
		}

		return $scalarTypes;
	}

	public function getConstantScalarValues(): array
	{
		$values = [];
		foreach ($this->types as $type) {
			foreach ($type->getConstantScalarValues() as $value) {
				$values[] = $value;
			}
		}

		return $values;
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
		return $this->isBoolean ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isBoolean());
	}

	public function isFloat(): TrinaryLogic
	{
		return $this->isFloat ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isFloat());
	}

	public function isInteger(): TrinaryLogic
	{
		return $this->isInteger ??= $this->intersectResults(static fn (Type $type): TrinaryLogic => $type->isInteger());
	}

	public function isGreaterThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $otherType->isSmallerThan($type, $phpVersion));
	}

	public function isGreaterThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->intersectResults(static fn (Type $type): TrinaryLogic => $otherType->isSmallerThanOrEqual($type, $phpVersion));
	}

	public function getSmallerType(PhpVersion $phpVersion): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getSmallerType($phpVersion));
	}

	public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getSmallerOrEqualType($phpVersion));
	}

	public function getGreaterType(PhpVersion $phpVersion): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getGreaterType($phpVersion));
	}

	public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->getGreaterOrEqualType($phpVersion));
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

	public function toAbsoluteNumber(): Type
	{
		$type = $this->intersectTypes(static fn (Type $type): Type => $type->toAbsoluteNumber());

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
			return TypeCombinator::union(
				new IntegerType(),
				$this,
			);
		}

		if ($this->isString()->yes()) {
			return $this;
		}

		return $this->intersectTypes(static fn (Type $type): Type => $type->toArrayKey());
	}

	public function toCoercedArgumentType(bool $strictTypes): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->toCoercedArgumentType($strictTypes));
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
			$result = $types[0];
			for ($i = 1, $count = count($types); $i < $count; $i++) {
				$result = TypeCombinator::intersect($result, $types[$i]);
			}
			return $result;
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if ($this->isArray()->yes() && $right->isArray()->yes()) {
			$changed = false;
			$newTypes = [];

			foreach ($this->types as $innerType) {
				$newKeyType = $cb($innerType->getIterableKeyType(), $right->getIterableKeyType());
				$newValueType = $cb($innerType->getIterableValueType(), $right->getIterableValueType());
				if ($newKeyType === $innerType->getIterableKeyType() && $newValueType === $innerType->getIterableValueType()) {
					$newTypes[] = $innerType;
					continue;
				}

				$changed = true;
				$newTypes[] = TypeTraverser::map($innerType, static function (Type $type, callable $traverse) use ($innerType, $newKeyType, $newValueType): Type {
					if ($type === $innerType->getIterableKeyType()) {
						return $newKeyType;
					}
					if ($type === $innerType->getIterableValueType()) {
						return $newValueType;
					}

					return $traverse($type);
				});
			}

			if (!$changed) {
				return $this;
			}

			$result = $newTypes[0];
			for ($i = 1, $count = count($newTypes); $i < $count; $i++) {
				$result = TypeCombinator::intersect($result, $newTypes[$i]);
			}
			return $result;
		}

		return $this;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => TypeCombinator::remove($type, $typeToRemove));
	}

	public function exponentiate(Type $exponent): Type
	{
		return $this->intersectTypes(static fn (Type $type): Type => $type->exponentiate($exponent));
	}

	public function getFiniteTypes(): array
	{
		$compare = [];
		foreach ($this->types as $type) {
			$oneType = [];
			foreach ($type->getFiniteTypes() as $finiteType) {
				if ($finiteType instanceof EnumCaseObjectType) {
					$oneType[$finiteType->getClassName() . '::' . $finiteType->getEnumCaseName()] = $finiteType;
					continue;
				}
				$oneType[$finiteType->describe(VerbosityLevel::typeOnly())] = $finiteType;
			}
			$compare[] = $oneType;
		}

		$result = array_values(array_intersect_key(...$compare));

		if (count($result) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return [];
		}

		return $result;
	}

	/**
	 * @param callable(Type $type): TrinaryLogic $getResult
	 * @param (callable(Type $type): bool)|null  $filter
	 */
	private function intersectResults(
		callable $getResult,
		?callable $filter = null,
	): TrinaryLogic
	{
		$types = $this->types;
		if ($filter !== null) {
			$types = array_filter($types, $filter);
		}
		if (count($types) === 0) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::lazyMaxMin($types, $getResult);
	}

	/**
	 * @param callable(Type $type): Type $getType
	 */
	private function intersectTypes(callable $getType): Type
	{
		$operands = array_map($getType, $this->types);
		$result = $operands[0];
		for ($i = 1, $count = count($operands); $i < $count; $i++) {
			$result = TypeCombinator::intersect($result, $operands[$i]);
		}
		return $result;
	}

	public function toPhpDocNode(): TypeNode
	{
		$baseTypes = [];
		$typesToDescribe = [];
		$skipTypeNames = [];

		$nonEmptyStr = false;
		$nonFalsyStr = false;
		$isList = $this->isList()->yes();
		$isArray = $this->isArray()->yes();
		$isNonEmptyArray = $this->isIterableAtLeastOnce()->yes();
		$describedTypes = [];

		foreach ($this->getSortedTypes() as $i => $type) {
			if ($type instanceof AccessoryNonEmptyStringType
				|| $type instanceof AccessoryLiteralStringType
				|| $type instanceof AccessoryNumericStringType
				|| $type instanceof AccessoryNonFalsyStringType
				|| $type instanceof AccessoryLowercaseStringType
				|| $type instanceof AccessoryUppercaseStringType
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

			if ($isList || $isArray) {
				if ($type instanceof ArrayType) {
					$keyType = $type->getKeyType();
					$valueType = $type->getItemType();
					if ($isList) {
						$isMixedValueType = $valueType instanceof MixedType && $valueType->describe(VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
						$identifierTypeNode = new IdentifierTypeNode($isNonEmptyArray ? 'non-empty-list' : 'list');
						if (!$isMixedValueType) {
							$describedTypes[$i] = new GenericTypeNode($identifierTypeNode, [
								$valueType->toPhpDocNode(),
							]);
						} else {
							$describedTypes[$i] = $identifierTypeNode;
						}
					} else {
						$isMixedKeyType = $keyType instanceof MixedType && $keyType->describe(VerbosityLevel::precise()) === 'mixed' && !$keyType->isExplicitMixed();
						$isMixedValueType = $valueType instanceof MixedType && $valueType->describe(VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
						$identifierTypeNode = new IdentifierTypeNode($isNonEmptyArray ? 'non-empty-array' : 'array');
						if (!$isMixedKeyType) {
							$describedTypes[$i] = new GenericTypeNode($identifierTypeNode, [
								$keyType->toPhpDocNode(),
								$valueType->toPhpDocNode(),
							]);
						} elseif (!$isMixedValueType) {
							$describedTypes[$i] = new GenericTypeNode($identifierTypeNode, [
								$valueType->toPhpDocNode(),
							]);
						} else {
							$describedTypes[$i] = $identifierTypeNode;
						}
					}
					continue;
				} elseif ($type instanceof ConstantArrayType) {
					$constantArrayTypeNode = $type->toPhpDocNode();
					if ($constantArrayTypeNode instanceof ArrayShapeNode) {
						$newKind = $constantArrayTypeNode->kind;
						if ($isList) {
							if ($isNonEmptyArray && !$type->isIterableAtLeastOnce()->yes()) {
								$newKind = ArrayShapeNode::KIND_NON_EMPTY_LIST;
							} else {
								$newKind = ArrayShapeNode::KIND_LIST;
							}
						} elseif ($isNonEmptyArray && !$type->isIterableAtLeastOnce()->yes()) {
							$newKind = ArrayShapeNode::KIND_NON_EMPTY_ARRAY;
						}

						if ($newKind !== $constantArrayTypeNode->kind) {
							if ($constantArrayTypeNode->sealed) {
								$constantArrayTypeNode = ArrayShapeNode::createSealed($constantArrayTypeNode->items, $newKind);
							} else {
								$constantArrayTypeNode = ArrayShapeNode::createUnsealed($constantArrayTypeNode->items, $constantArrayTypeNode->unsealedType, $newKind);
							}
						}

						$describedTypes[$i] = $constantArrayTypeNode;
						continue;
					}
				}
				if ($type instanceof NonEmptyArrayType || $type instanceof AccessoryArrayListType) {
					continue;
				}
			}

			if (!$type instanceof AccessoryType) {
				$baseTypes[$i] = $type;
				continue;
			}

			$accessoryPhpDocNode = $type->toPhpDocNode();
			if ($accessoryPhpDocNode instanceof IdentifierTypeNode && $accessoryPhpDocNode->name === '') {
				continue;
			}

			$typesToDescribe[$i] = $type;
		}

		foreach ($baseTypes as $i => $type) {
			$typeNode = $type->toPhpDocNode();
			if ($typeNode instanceof GenericTypeNode && $typeNode->type->name === 'array') {
				$nonEmpty = false;
				$typeName = 'array';
				foreach ($typesToDescribe as $j => $typeToDescribe) {
					if ($typeToDescribe instanceof AccessoryArrayListType) {
						$typeName = 'list';
						if (count($typeNode->genericTypes) > 1) {
							array_shift($typeNode->genericTypes);
						}
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

				$describedTypes[$i] = new GenericTypeNode(
					new IdentifierTypeNode($typeName),
					$typeNode->genericTypes,
				);
				continue;
			}

			if ($typeNode instanceof IdentifierTypeNode && in_array($typeNode->name, $skipTypeNames, true)) {
				continue;
			}

			$describedTypes[$i] = $typeNode;
		}

		foreach ($typesToDescribe as $i => $typeToDescribe) {
			$describedTypes[$i] = $typeToDescribe->toPhpDocNode();
		}

		ksort($describedTypes);

		$describedTypes = array_values($describedTypes);

		if (count($describedTypes) === 1) {
			return $describedTypes[0];
		}

		return new IntersectionTypeNode($describedTypes);
	}

	public function hasTemplateOrLateResolvableType(): bool
	{
		foreach ($this->types as $type) {
			if (!$type->hasTemplateOrLateResolvableType()) {
				continue;
			}

			return true;
		}

		return false;
	}

}
