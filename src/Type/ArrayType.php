<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
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
use function array_merge;
use function count;
use function sprintf;

/** @api */
class ArrayType implements Type
{

	use ArrayTypeTrait;
	use MaybeCallableTypeTrait;
	use NonObjectTypeTrait;
	use UndecidedBooleanTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonGeneralizableTypeTrait;

	private Type $keyType;

	/** @api */
	public function __construct(Type $keyType, private Type $itemType)
	{
		if ($keyType->describe(VerbosityLevel::value()) === '(int|string)') {
			$keyType = new MixedType();
		}
		if ($keyType instanceof StrictMixedType && !$keyType instanceof TemplateStrictMixedType) {
			$keyType = new UnionType([new StringType(), new IntegerType()]);
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
		$keyType = $this->keyType;
		if ($keyType instanceof MixedType && !$keyType instanceof TemplateMixedType) {
			return new BenevolentUnionType([new IntegerType(), new StringType()]);
		}
		if ($keyType instanceof StrictMixedType) {
			return new BenevolentUnionType([new IntegerType(), new StringType()]);
		}

		return $keyType;
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
		if (IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($this->getKeyType())->no()) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
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
		$offsetType = $offsetType->toArrayKey();

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
				$offsetType = $this->keyType;
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

			return TypeCombinator::intersect(
				new self(
					TypeCombinator::union($this->keyType, $offsetType),
					TypeCombinator::union($this->itemType, $valueType),
				),
				new HasOffsetValueType($offsetType, $valueType),
				new NonEmptyArrayType(),
			);
		}

		return TypeCombinator::intersect(
			new self(
				TypeCombinator::union($this->keyType, $offsetType),
				$unionValues ? TypeCombinator::union($this->itemType, $valueType) : $valueType,
			),
			new NonEmptyArrayType(),
		);
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
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

		return new self($otherArraysType->getIterableKeyType(), $this->getIterableValueType());
	}

	public function popArray(): Type
	{
		return $this;
	}

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		return $this;
	}

	public function searchArray(Type $needleType): Type
	{
		return TypeCombinator::union($this->getIterableKeyType(), new ConstantBooleanType(false));
	}

	public function shiftArray(): Type
	{
		return $this;
	}

	public function shuffleArray(): Type
	{
		return TypeCombinator::intersect(new self(new IntegerType(), $this->itemType), new AccessoryArrayListType());
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return $this;
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
		return TypeCombinator::union(
			new ConstantIntegerType(0),
			new ConstantIntegerType(1),
		);
	}

	public function toFloat(): Type
	{
		return TypeCombinator::union(
			new ConstantFloatType(0.0),
			new ConstantFloatType(1.0),
		);
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

			return new self($keyType, $itemType);
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

			return new self($keyType, $itemType);
		}

		return $this;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove->isConstantArray()->yes() && $typeToRemove->isIterableAtLeastOnce()->no()) {
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

}
