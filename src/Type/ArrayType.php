<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Rules\Arrays\AllowedArrayKeysTypes;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateStrictMixedType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\ArrayTypeTrait;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use PHPStan\Type\Traverser\UnsafeArrayStringKeyCastingTraverser;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function sprintf;
use function strtolower;
use function strtoupper;
use const CASE_LOWER;
use const CASE_UPPER;

/** @api */
class ArrayType implements Type
{

	use ArrayTypeTrait;
	use MaybeCallableTypeTrait;
	use NonObjectTypeTrait;
	use UndecidedBooleanTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonGeneralizableTypeTrait;

	private const TRUNCATE_ACCESSORIES_LIMIT = 8;

	private Type $keyType;

	private ?Type $cachedIterableKeyType = null;

	private ?TrinaryLogic $isList = null;

	/** @api */
	public function __construct(Type $keyType, private Type $itemType)
	{
		if (in_array($keyType->describe(VerbosityLevel::value()), ['(int|string)', '(int|non-decimal-int-string)'], true)) {
			$keyType = new MixedType();
		}
		if ($keyType instanceof StrictMixedType && !$keyType instanceof TemplateStrictMixedType) {
			$keyType = (new UnionType([new StringType(), new IntegerType()]))->toArrayKey();
		}

		$this->keyType = $keyType;
	}

	public function getKeyType(): Type
	{
		return $this->keyType;
	}

	public function getItemType(): Type
	{
		return $this->itemType;
	}

	/**
	 * Build a same-kind array with new key/item types. Subclasses
	 * (e.g. {@see TemplateArrayType}) override this to preserve their
	 * extra metadata across array-mutating operations such as offset
	 * writes and unsets.
	 */
	protected function withTypes(Type $keyType, Type $itemType): self
	{
		return new self($keyType, $itemType);
	}

	public function getReferencedClasses(): array
	{
		return array_merge(
			$this->keyType->getReferencedClasses(),
			$this->getItemType()->getReferencedClasses(),
		);
	}

	public function getConstantArrays(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		if ($type instanceof ConstantArrayType) {
			$result = AcceptsResult::createYes();
			$thisKeyType = $this->keyType;
			$itemType = $this->getItemType();
			foreach ($type->getKeyTypes() as $i => $keyType) {
				$valueType = $type->getValueTypes()[$i];
				$acceptsKey = $thisKeyType->accepts($keyType, $strictTypes);
				$acceptsValue = $itemType->accepts($valueType, $strictTypes);
				$result = $result->and($acceptsKey)->and($acceptsValue);
			}

			return $result;
		}

		if ($type instanceof ArrayType) {
			return $this->getItemType()->accepts($type->getItemType(), $strictTypes)
				->and($this->keyType->accepts($type->keyType, $strictTypes));
		}

		return AcceptsResult::createNo();
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self || $type instanceof ConstantArrayType) {
			return $this->getItemType()->isSuperTypeOf($type->getItemType())
				->and($this->getIterableKeyType()->isSuperTypeOf($type->getIterableKeyType()));
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return IsSuperTypeOfResult::createNo();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->getItemType()->equals($type->getIterableValueType())
			&& $this->keyType->equals($type->keyType);
	}

	public function describe(VerbosityLevel $level): string
	{
		$isMixedKeyType = $this->keyType instanceof MixedType && $this->keyType->describe(VerbosityLevel::precise()) === 'mixed' && !$this->keyType->isExplicitMixed();
		$isMixedItemType = $this->itemType instanceof MixedType && $this->itemType->describe(VerbosityLevel::precise()) === 'mixed' && !$this->itemType->isExplicitMixed();

		$valueHandler = function () use ($level, $isMixedKeyType, $isMixedItemType): string {
			if ($isMixedKeyType || $this->keyType instanceof NeverType) {
				if ($isMixedItemType || $this->itemType instanceof NeverType) {
					return 'array';
				}

				return sprintf('array<%s>', $this->itemType->describe($level));
			}

			return sprintf('array<%s, %s>', $this->keyType->describe($level), $this->itemType->describe($level));
		};

		return $level->handle(
			$valueHandler,
			$valueHandler,
			function () use ($level, $isMixedKeyType, $isMixedItemType): string {
				if ($isMixedKeyType) {
					if ($isMixedItemType) {
						return 'array';
					}

					return sprintf('array<%s>', $this->itemType->describe($level));
				}

				return sprintf('array<%s, %s>', $this->keyType->describe($level), $this->itemType->describe($level));
			},
		);
	}

	public function generalizeValues(): self
	{
		return new self($this->keyType, $this->itemType->generalize(GeneralizePrecision::lessSpecific()));
	}

	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type
	{
		return $this->getKeysArray();
	}

	public function getKeysArray(): Type
	{
		return TypeCombinator::intersect(new self(new IntegerType(), $this->getIterableKeyType()), new AccessoryArrayListType());
	}

	public function getValuesArray(): Type
	{
		return TypeCombinator::intersect(new self(new IntegerType(), $this->itemType), new AccessoryArrayListType());
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getArraySize(): Type
	{
		return IntegerRangeType::fromInterval(0, null);
	}

	public function getIterableKeyType(): Type
	{
		if ($this->cachedIterableKeyType !== null) {
			return $this->cachedIterableKeyType;
		}
		$keyType = $this->keyType;
		if ($keyType instanceof MixedType && !$keyType instanceof TemplateMixedType) {
			$keyType = (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey();
		}
		if ($keyType instanceof StrictMixedType) {
			$keyType = (new BenevolentUnionType([new IntegerType(), new StringType()]))->toArrayKey();
		}

		return $this->cachedIterableKeyType = UnsafeArrayStringKeyCastingTraverser::castKeyType($keyType);
	}

	public function getFirstIterableKeyType(): Type
	{
		return $this->getIterableKeyType();
	}

	public function getLastIterableKeyType(): Type
	{
		return $this->getIterableKeyType();
	}

	public function getIterableValueType(): Type
	{
		return $this->getItemType();
	}

	public function getFirstIterableValueType(): Type
	{
		return $this->getItemType();
	}

	public function getLastIterableValueType(): Type
	{
		return $this->getItemType();
	}

	public function isConstantArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isList(): TrinaryLogic
	{
		if ($this->isList === null) {
			if (IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($this->getKeyType())->no()) {
				return $this->isList = TrinaryLogic::createNo();
			}

			if ($this->getKeyType()->isSuperTypeOf(new ConstantIntegerType(0))->no()) {
				return $this->isList = TrinaryLogic::createNo();
			}

			return $this->isList = TrinaryLogic::createMaybe();
		}

		return $this->isList;
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		if ($type->isInteger()->yes()) {
			return new ConstantBooleanType(false);
		}

		return new BooleanType();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		$offsetArrayKeyType = $offsetType->toArrayKey();
		if ($offsetArrayKeyType instanceof ErrorType) {
			$allowedArrayKeys = AllowedArrayKeysTypes::getType();
			$offsetArrayKeyType = TypeCombinator::intersect($allowedArrayKeys, $offsetType)->toArrayKey();
			if ($offsetArrayKeyType instanceof NeverType) {
				return TrinaryLogic::createNo();
			}
		}
		$offsetType = $offsetArrayKeyType;

		if ($this->getKeyType()->isSuperTypeOf($offsetType)->no()
			&& ($offsetType->isString()->no() || !$offsetType->isConstantScalarValue()->no())
		) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		$offsetType = $offsetType->toArrayKey();
		if ($this->getKeyType()->isSuperTypeOf($offsetType)->no()
			&& ($offsetType->isString()->no() || !$offsetType->isConstantScalarValue()->no())
		) {
			return new ErrorType();
		}

		$type = $this->getItemType();
		if ($type instanceof ErrorType) {
			return new MixedType();
		}

		return $type;
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		if ($offsetType === null) {
			$isKeyTypeInteger = $this->keyType->isInteger();
			if ($isKeyTypeInteger->no()) {
				$offsetType = new IntegerType();
			} elseif ($isKeyTypeInteger->yes()) {
				/** @var list<ConstantIntegerType> $constantScalars */
				$constantScalars = $this->keyType->getConstantScalarTypes();
				if (count($constantScalars) > 0) {
					foreach ($constantScalars as $constantScalar) {
						$constantScalars[] = ConstantTypeHelper::getTypeFromValue($constantScalar->getValue() + 1);
					}

					$offsetType = TypeCombinator::union(...$constantScalars);
				} else {
					$offsetType = $this->keyType;
				}
			} else {
				$integerTypes = [];
				TypeTraverser::map($this->keyType, static function (Type $type, callable $traverse) use (&$integerTypes): Type {
					if ($type instanceof UnionType) {
						return $traverse($type);
					}

					$isInteger = $type->isInteger();
					if ($isInteger->yes()) {
						$integerTypes[] = $type;
					}

					return $type;
				});
				if (count($integerTypes) === 0) {
					$offsetType = $this->keyType;
				} else {
					$offsetType = TypeCombinator::union(...$integerTypes);
				}
			}
		} else {
			$offsetType = $offsetType->toArrayKey();
		}

		if ($offsetType instanceof ConstantStringType || $offsetType instanceof ConstantIntegerType) {
			if ($offsetType->isSuperTypeOf($this->keyType)->yes()) {
				$builder = ConstantArrayTypeBuilder::createEmpty();
				$builder->setOffsetValueType($offsetType, $valueType);
				return $builder->getArray();
			}

			return new IntersectionType([
				$this->withTypes(
					TypeCombinator::union($this->keyType, $offsetType),
					TypeCombinator::union($this->itemType, $valueType),
				),
				new HasOffsetValueType($offsetType, $valueType),
				new NonEmptyArrayType(),
			]);
		}

		return new IntersectionType([
			$this->withTypes(
				TypeCombinator::union($this->keyType, $offsetType),
				$unionValues ? TypeCombinator::union($this->itemType, $valueType) : $valueType,
			),
			new NonEmptyArrayType(),
		]);
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		if ($this->itemType->isConstantArray()->yes() && $valueType->isConstantArray()->yes()) {
			$newItemTypes = [];

			foreach ($valueType->getConstantArrays() as $constArray) {
				$newItemType = $this->itemType;
				$optionalKeyTypes = [];
				foreach ($constArray->getKeyTypes() as $i => $keyType) {
					$newItemType = $newItemType->setExistingOffsetValueType($keyType, $constArray->getOffsetValueType($keyType));

					if (!$constArray->isOptionalKey($i)) {
						continue;
					}

					$optionalKeyTypes[] = $keyType;
				}
				$newItemTypes[] = $newItemType;

				if ($optionalKeyTypes === []) {
					continue;
				}

				foreach ($optionalKeyTypes as $keyType) {
					$newItemType = $newItemType->unsetOffset($keyType);
				}
				$newItemTypes[] = $newItemType;
			}

			$newItemType = TypeCombinator::union(...$newItemTypes);
			if ($newItemType !== $this->itemType) {
				return new self(
					$this->keyType,
					$newItemType,
				);
			}
		}

		return new self(
			$this->keyType,
			TypeCombinator::union($this->itemType, $valueType),
		);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		$offsetType = $offsetType->toArrayKey();

		if (
			($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType)
			&& !$this->keyType->isSuperTypeOf($offsetType)->no()
		) {
			$keyType = TypeCombinator::remove($this->keyType, $offsetType);
			if ($keyType instanceof NeverType) {
				return new ConstantArrayType([], []);
			}

			return new self($keyType, $this->itemType);
		}

		return $this;
	}

	public function fillKeysArray(Type $valueType): Type
	{
		$itemType = $this->getItemType();
		if ($itemType->isInteger()->no()) {
			$stringKeyType = $itemType->toString();
			if ($stringKeyType instanceof ErrorType) {
				return $stringKeyType;
			}

			return new ArrayType($stringKeyType, $valueType);
		}

		return new ArrayType($itemType, $valueType);
	}

	public function flipArray(): Type
	{
		return new self($this->getIterableValueType()->toArrayKey(), $this->getIterableKeyType());
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		$isKeySuperType = $otherArraysType->getIterableKeyType()->isSuperTypeOf($this->getIterableKeyType());
		if ($isKeySuperType->no()) {
			return ConstantArrayTypeBuilder::createEmpty()->getArray();
		}

		if ($isKeySuperType->yes()) {
			return $this;
		}

		return $this->withTypes($otherArraysType->getIterableKeyType(), $this->getIterableValueType());
	}

	public function popArray(): Type
	{
		return $this;
	}

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		return $this;
	}

	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type
	{
		$strict ??= TrinaryLogic::createMaybe();
		if ($strict->yes() && $this->getIterableValueType()->isSuperTypeOf($needleType)->no()) {
			return new ConstantBooleanType(false);
		}

		return TypeCombinator::union($this->getIterableKeyType(), new ConstantBooleanType(false));
	}

	public function shiftArray(): Type
	{
		return $this;
	}

	public function shuffleArray(): Type
	{
		return new IntersectionType([$this->withTypes(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $this->itemType), new AccessoryArrayListType()]);
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		if ((new ConstantIntegerType(0))->isSuperTypeOf($lengthType)->yes()) {
			return new ConstantArrayType([], []);
		}

		if ($preserveKeys->no() && $this->keyType->isInteger()->yes()) {
			return new IntersectionType([$this->withTypes(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $this->itemType), new AccessoryArrayListType()]);
		}

		return $this;
	}

	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type
	{
		$replacementArrayType = $replacementType->toArray();
		$replacementArrayTypeIsIterableAtLeastOnce = $replacementArrayType->isIterableAtLeastOnce();

		if ((new ConstantIntegerType(0))->isSuperTypeOf($offsetType)->yes() && $lengthType->isNull()->yes() && $replacementArrayTypeIsIterableAtLeastOnce->no()) {
			return new ConstantArrayType([], []);
		}

		$existingArrayKeyType = $this->getIterableKeyType();
		$keyType = TypeTraverser::map($existingArrayKeyType, static function (Type $type, callable $traverse): Type {
			if ($type instanceof UnionType) {
				return $traverse($type);
			}

			if ($type->isInteger()->yes()) {
				return IntegerRangeType::createAllGreaterThanOrEqualTo(0);
			}

			return $type;
		});

		$arrayType = $this->withTypes(
			TypeCombinator::union($keyType, $replacementArrayType->getKeysArray()->getIterableKeyType()),
			TypeCombinator::union($this->getIterableValueType(), $replacementArrayType->getIterableValueType()),
		);

		$accessories = [];
		if ($replacementArrayTypeIsIterableAtLeastOnce->yes()) {
			$accessories[] = new NonEmptyArrayType();
		}
		if ($existingArrayKeyType->isInteger()->yes()) {
			$accessories[] = new AccessoryArrayListType();
		}
		if (count($accessories) > 0) {
			$accessories[] = $arrayType;

			return new IntersectionType($accessories);
		}

		return $arrayType;
	}

	public function makeListMaybe(): Type
	{
		// `ArrayType` doesn't carry list-ness on its own — that's an
		// `AccessoryArrayListType` in an enclosing `IntersectionType`.
		return $this;
	}

	public function truncateListToSize(Type $sizeType): Type
	{
		[$min, $max] = ConstantArrayType::extractTruncateListBounds($sizeType);

		// `isList()` is deliberately NOT checked here — see the matching
		// note on `ConstantArrayType::truncateListToSize`. The call site
		// has already established outer list-ness.
		if (
			$min === null
			|| $min >= ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT
			|| !$this->getKeyType()->isSuperTypeOf(IntegerRangeType::fromInterval(0, ($max ?? $min) - 1))->yes()
		) {
			return TypeCombinator::intersect($this, new NonEmptyArrayType());
		}

		if ($max !== null) {
			// Bounded range — `ArrayType` doesn't carry per-offset types, so
			// rebuild via the same CAT builder logic as `ConstantArrayType`.
			// The values come from `$this->getOffsetValueType()` (which on a
			// general `ArrayType` collapses to the iterable value type).
			if ($max - $min > ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
				return TypeCombinator::intersect($this, new NonEmptyArrayType());
			}

			$builder = ConstantArrayTypeBuilder::createEmpty();
			for ($i = 0; $i < $min; $i++) {
				$offsetType = new ConstantIntegerType($i);
				$builder->setOffsetValueType($offsetType, $this->getOffsetValueType($offsetType), false);
			}
			for ($i = $min; $i < $max; $i++) {
				$offsetType = new ConstantIntegerType($i);
				$builder->setOffsetValueType($offsetType, $this->getOffsetValueType($offsetType), true);
			}

			$builtArray = $builder->getArray();
			if (!$builder->isList()) {
				$constantArrays = $builtArray->getConstantArrays();
				if (count($constantArrays) === 1) {
					$builtArray = $constantArrays[0]->makeList();
				}
			}

			return $builtArray;
		}

		// Unbounded max on a general `ArrayType` list: we can't enumerate the
		// trailing entries, so anchor the lower bound with
		// `HasOffsetValueType` accessories (skipping offset 0 — already
		// implied by `NonEmptyArrayType`).
		$intersection = [$this, new NonEmptyArrayType()];
		$zero = new ConstantIntegerType(0);
		$added = 0;
		for ($i = 0; $i < $min; $i++) {
			$offsetType = new ConstantIntegerType($i);
			if ($zero->isSuperTypeOf($offsetType)->yes()) {
				continue;
			}
			if ($added > self::TRUNCATE_ACCESSORIES_LIMIT) {
				break;
			}

			$intersection[] = new HasOffsetValueType($offsetType, $this->getOffsetValueType($offsetType));
			$added++;
		}

		return TypeCombinator::intersect(...$intersection);
	}

	public function mapValueType(callable $cb): Type
	{
		return $this->withTypes($this->keyType, $cb($this->getItemType()));
	}

	public function mapKeyType(callable $cb): Type
	{
		return $this->withTypes($cb($this->keyType), $this->getItemType());
	}

	public function makeAllArrayKeysOptional(): Type
	{
		// `ArrayType` already models arbitrary key subsets.
		return $this;
	}

	public function changeKeyCaseArray(?int $case): Type
	{
		$newKeyType = TypeTraverser::map($this->keyType, static function (Type $type, callable $traverse) use ($case): Type {
			if ($type instanceof UnionType) {
				return $traverse($type);
			}

			$constantStrings = $type->getConstantStrings();
			if (count($constantStrings) > 0) {
				return TypeCombinator::union(
					...array_map(
						static fn (ConstantStringType $type): Type => self::foldConstantStringKeyCase($type, $case),
						$constantStrings,
					),
				);
			}

			if ($type->isString()->yes()) {
				$types = [new StringType()];
				if ($type->isNonFalsyString()->yes()) {
					$types[] = new AccessoryNonFalsyStringType();
				} elseif ($type->isNonEmptyString()->yes()) {
					$types[] = new AccessoryNonEmptyStringType();
				}
				if ($type->isNumericString()->yes()) {
					$types[] = new AccessoryNumericStringType();
				}
				if ($case === CASE_LOWER) {
					$types[] = new AccessoryLowercaseStringType();
				} elseif ($case === CASE_UPPER) {
					$types[] = new AccessoryUppercaseStringType();
				}

				if (count($types) === 1) {
					return $types[0];
				}
				return new IntersectionType($types);
			}

			return $type;
		});

		return $this->withTypes($newKeyType, $this->getItemType());
	}

	public function filterArrayRemovingFalsey(): Type
	{
		$falseyTypes = StaticTypeFactory::falsey();
		$valueType = TypeCombinator::remove($this->getItemType(), $falseyTypes);
		if ($valueType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		return $this->withTypes($this->keyType, $valueType);
	}

	private static function foldConstantStringKeyCase(ConstantStringType $type, ?int $case): Type
	{
		if ($case === CASE_LOWER) {
			return new ConstantStringType(strtolower($type->getValue()));
		}
		if ($case === CASE_UPPER) {
			return new ConstantStringType(strtoupper($type->getValue()));
		}

		return TypeCombinator::union(
			new ConstantStringType(strtolower($type->getValue())),
			new ConstantStringType(strtoupper($type->getValue())),
		);
	}

	public function isCallable(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe()->and($this->itemType->isString());
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		if ($this->isCallable()->no()) {
			throw new ShouldNotHappenException();
		}

		return [new TrivialParametersAcceptor()];
	}

	public function toInteger(): Type
	{
		return new UnionType([
			new ConstantIntegerType(0),
			new ConstantIntegerType(1),
		]);
	}

	public function toFloat(): Type
	{
		return new UnionType([
			new ConstantFloatType(0.0),
			new ConstantFloatType(1.0),
		]);
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if ($receivedType->isArray()->yes()) {
			$keyTypeMap = $this->getIterableKeyType()->inferTemplateTypes($receivedType->getIterableKeyType());
			$itemTypeMap = $this->getItemType()->inferTemplateTypes($receivedType->getIterableValueType());

			return $keyTypeMap->union($itemTypeMap);
		}

		return TemplateTypeMap::createEmpty();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());

		return array_merge(
			$this->getIterableKeyType()->getReferencedTemplateTypes($variance),
			$this->getItemType()->getReferencedTemplateTypes($variance),
		);
	}

	public function traverse(callable $cb): Type
	{
		$keyType = $cb($this->keyType);
		$itemType = $cb($this->itemType);

		if ($keyType !== $this->keyType || $itemType !== $this->itemType) {
			if ($keyType instanceof NeverType && $itemType instanceof NeverType) {
				return new ConstantArrayType([], []);
			}

			return $this->withTypes($keyType, $itemType);
		}

		return $this;
	}

	public function toPhpDocNode(): TypeNode
	{
		$isMixedKeyType = $this->keyType instanceof MixedType && $this->keyType->describe(VerbosityLevel::precise()) === 'mixed' && !$this->keyType->isExplicitMixed();
		$isMixedItemType = $this->itemType instanceof MixedType && $this->itemType->describe(VerbosityLevel::precise()) === 'mixed' && !$this->itemType->isExplicitMixed();

		if ($isMixedKeyType) {
			if ($isMixedItemType) {
				return new IdentifierTypeNode('array');
			}

			return new GenericTypeNode(
				new IdentifierTypeNode('array'),
				[
					$this->itemType->toPhpDocNode(),
				],
			);
		}

		return new GenericTypeNode(
			new IdentifierTypeNode('array'),
			[
				$this->keyType->toPhpDocNode(),
				$this->itemType->toPhpDocNode(),
			],
		);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		$keyType = $cb($this->keyType, $right->getIterableKeyType());
		$itemType = $cb($this->itemType, $right->getIterableValueType());

		if ($keyType !== $this->keyType || $itemType !== $this->itemType) {
			if ($keyType instanceof NeverType && $itemType instanceof NeverType) {
				return new ConstantArrayType([], []);
			}

			return $this->withTypes($keyType, $itemType);
		}

		return $this;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove->isConstantArray()->yes() && $typeToRemove->isIterableAtLeastOnce()->no()) {
			return TypeCombinator::intersect($this, new NonEmptyArrayType());
		}

		if ($typeToRemove->isSuperTypeOf(new ConstantArrayType([], []))->yes()) {
			return TypeCombinator::intersect($this, new NonEmptyArrayType());
		}

		if ($typeToRemove instanceof NonEmptyArrayType) {
			return new ConstantArrayType([], []);
		}

		return null;
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function hasTemplateOrLateResolvableType(): bool
	{
		return $this->keyType->hasTemplateOrLateResolvableType() || $this->itemType->hasTemplateOrLateResolvableType();
	}

}
