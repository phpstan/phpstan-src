<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function array_merge;
use function is_float;
use function is_int;
use function key;
use function sprintf;

/** @api */
class ArrayType implements Type
{

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
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return array_merge(
			$this->keyType->getReferencedClasses(),
			$this->getItemType()->getReferencedClasses(),
		);
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		if ($type instanceof ConstantArrayType) {
			$result = TrinaryLogic::createYes();
			$thisKeyType = $this->keyType;
			$itemType = $this->getItemType();
			foreach ($type->getKeyTypes() as $i => $keyType) {
				$valueType = $type->getValueTypes()[$i];
				$result = $result->and($thisKeyType->accepts($keyType, $strictTypes))->and($itemType->accepts($valueType, $strictTypes));
			}

			return $result;
		}

		if ($type instanceof ArrayType) {
			return $this->getItemType()->accepts($type->getItemType(), $strictTypes)
				->and($this->keyType->accepts($type->keyType, $strictTypes));
		}

		return TrinaryLogic::createNo();
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return $this->getItemType()->isSuperTypeOf($type->getItemType())
				->and($this->keyType->isSuperTypeOf($type->keyType));
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return TrinaryLogic::createNo();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& !$type instanceof ConstantArrayType
			&& $this->getItemType()->equals($type->getItemType())
			&& $this->keyType->equals($type->keyType);
	}

	public function describe(VerbosityLevel $level): string
	{
		$isMixedKeyType = $this->keyType instanceof MixedType && $this->keyType->describe(VerbosityLevel::precise()) === 'mixed';
		$isMixedItemType = $this->itemType instanceof MixedType && $this->itemType->describe(VerbosityLevel::precise()) === 'mixed';

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

	public function generalizeKeys(): self
	{
		return new self($this->keyType->generalize(GeneralizePrecision::lessSpecific()), $this->itemType);
	}

	public function generalizeValues(): self
	{
		return new self($this->keyType, $this->itemType->generalize(GeneralizePrecision::lessSpecific()));
	}

	public function getKeysArray(): self
	{
		return new self(new IntegerType(), $this->keyType);
	}

	public function getValuesArray(): self
	{
		return new self(new IntegerType(), $this->itemType);
	}

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
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

	public function getIterableValueType(): Type
	{
		return $this->getItemType();
	}

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		$offsetType = self::castToArrayKeyType($offsetType);
		if ($this->getKeyType()->isSuperTypeOf($offsetType)->no()) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		$offsetType = self::castToArrayKeyType($offsetType);
		if ($this->getKeyType()->isSuperTypeOf($offsetType)->no()) {
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
			$offsetType = new IntegerType();
		}

		if (
			($offsetType instanceof ConstantStringType || $offsetType instanceof ConstantIntegerType)
			&& $offsetType->isSuperTypeOf($this->keyType)->yes()
		) {
			$builder = ConstantArrayTypeBuilder::createEmpty();
			$builder->setOffsetValueType($offsetType, $valueType);
			return $builder->getArray();
		}

		return TypeCombinator::intersect(new self(
			TypeCombinator::union($this->keyType, self::castToArrayKeyType($offsetType)),
			$unionValues ? TypeCombinator::union($this->itemType, $valueType) : $valueType,
		), new NonEmptyArrayType());
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this;
	}

	public function isCallable(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe()->and((new StringType())->isSuperTypeOf($this->itemType));
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

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toString(): Type
	{
		return new ErrorType();
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

	public function toArray(): Type
	{
		return $this;
	}

	public function count(): Type
	{
		return IntegerRangeType::fromInterval(0, null);
	}

	public static function castToArrayKeyType(Type $offsetType): Type
	{
		return TypeTraverser::map($offsetType, static function (Type $offsetType, callable $traverse): Type {
			if ($offsetType instanceof TemplateType) {
				return $offsetType;
			}

			if ($offsetType instanceof ConstantScalarType) {
				$keyValue = $offsetType->getValue();
				if (is_float($keyValue)) {
					$keyValue = (int) $keyValue;
				}
				/** @var int|string $offsetValue */
				$offsetValue = key([$keyValue => null]);
				return is_int($offsetValue) ? new ConstantIntegerType($offsetValue) : new ConstantStringType($offsetValue);
			}

			if ($offsetType instanceof IntegerType) {
				return $offsetType;
			}

			if ($offsetType instanceof BooleanType) {
				return new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]);
			}

			if ($offsetType instanceof UnionType) {
				return $traverse($offsetType);
			}

			if ($offsetType instanceof FloatType || $offsetType->isNumericString()->yes()) {
				return new IntegerType();
			}

			if ($offsetType->isString()->yes()) {
				return $offsetType;
			}

			if ($offsetType instanceof IntersectionType) {
				return $traverse($offsetType);
			}

			return new UnionType([new IntegerType(), new StringType()]);
		});
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if ($receivedType->isArray()->yes()) {
			$keyTypeMap = $this->getKeyType()->inferTemplateTypes($receivedType->getIterableKeyType());
			$itemTypeMap = $this->getItemType()->inferTemplateTypes($receivedType->getIterableValueType());

			return $keyTypeMap->union($itemTypeMap);
		}

		return TemplateTypeMap::createEmpty();
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$keyVariance = $positionVariance;
		$itemVariance = $positionVariance;

		if (!$positionVariance->contravariant()) {
			$keyType = $this->getKeyType();
			if ($keyType instanceof TemplateType) {
				$keyVariance = $keyType->getVariance();
			}

			$itemType = $this->getItemType();
			if ($itemType instanceof TemplateType) {
				$itemVariance = $itemType->getVariance();
			}
		}

		return array_merge(
			$this->getKeyType()->getReferencedTemplateTypes($keyVariance),
			$this->getItemType()->getReferencedTemplateTypes($itemVariance),
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

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove instanceof ConstantArrayType && $typeToRemove->isIterableAtLeastOnce()->no()) {
			return TypeCombinator::intersect($this, new NonEmptyArrayType());
		}

		if ($typeToRemove instanceof NonEmptyArrayType) {
			return new ConstantArrayType([], []);
		}

		if ($this instanceof ConstantArrayType && $typeToRemove instanceof HasOffsetType) {
			return $this->unsetOffset($typeToRemove->getOffsetType());
		}

		if ($this instanceof ConstantArrayType && $typeToRemove instanceof HasOffsetValueType) {
			return $this->unsetOffset($typeToRemove->getOffsetType());
		}

		return null;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['keyType'],
			$properties['itemType'],
		);
	}

}
