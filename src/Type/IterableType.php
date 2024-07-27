<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\MaybeArrayTypeTrait;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\MaybeObjectTypeTrait;
use PHPStan\Type\Traits\MaybeOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use Traversable;
use function array_merge;
use function sprintf;

/** @api */
class IterableType implements CompoundType
{

	use MaybeArrayTypeTrait;
	use MaybeCallableTypeTrait;
	use MaybeObjectTypeTrait;
	use MaybeOffsetAccessibleTypeTrait;
	use UndecidedBooleanTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonGeneralizableTypeTrait;

	/** @api */
	public function __construct(
		private Type $keyType,
		private Type $itemType,
	)
	{
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

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getObjectClassReflections(): array
	{
		return [];
	}

	public function getConstantStrings(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type->isConstantArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
			return AcceptsResult::createYes();
		}
		if ($type->isIterable()->yes()) {
			return $this->getIterableValueType()->acceptsWithReason($type->getIterableValueType(), $strictTypes)
				->and($this->getIterableKeyType()->acceptsWithReason($type->getIterableKeyType(), $strictTypes));
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return AcceptsResult::createNo();
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return $type->isIterable()
			->and($this->getIterableValueType()->isSuperTypeOf($type->getIterableValueType()))
			->and($this->getIterableKeyType()->isSuperTypeOf($type->getIterableKeyType()));
	}

	public function isSuperTypeOfMixed(Type $type): TrinaryLogic
	{
		return $type->isIterable()
			->and($this->isNestedTypeSuperTypeOf($this->getIterableValueType(), $type->getIterableValueType()))
			->and($this->isNestedTypeSuperTypeOf($this->getIterableKeyType(), $type->getIterableKeyType()));
	}

	private function isNestedTypeSuperTypeOf(Type $a, Type $b): TrinaryLogic
	{
		if (!$a instanceof MixedType || !$b instanceof MixedType) {
			return $a->isSuperTypeOf($b);
		}

		if ($a instanceof TemplateMixedType || $b instanceof TemplateMixedType) {
			return $a->isSuperTypeOf($b);
		}

		if ($a->isExplicitMixed()) {
			if ($b->isExplicitMixed()) {
				return TrinaryLogic::createYes();
			}

			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createYes();
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof IntersectionType || $otherType instanceof UnionType) {
			return $otherType->isSuperTypeOf(new UnionType([
				new ArrayType($this->keyType, $this->itemType),
				new IntersectionType([
					new ObjectType(Traversable::class),
					$this,
				]),
			]));
		}

		if ($otherType instanceof self) {
			$limit = TrinaryLogic::createYes();
		} else {
			$limit = TrinaryLogic::createMaybe();
		}

		if ($otherType->isConstantArray()->yes() && $otherType->isIterableAtLeastOnce()->no()) {
			return TrinaryLogic::createMaybe();
		}

		return $limit->and(
			$otherType->isIterable(),
			$otherType->getIterableValueType()->isSuperTypeOf($this->itemType),
			$otherType->getIterableKeyType()->isSuperTypeOf($this->keyType),
		);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
	}

	public function isAcceptedWithReasonBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return new AcceptsResult($this->isSubTypeOf($acceptingType), []);
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		return $this->keyType->equals($type->keyType)
			&& $this->itemType->equals($type->itemType);
	}

	public function describe(VerbosityLevel $level): string
	{
		$isMixedKeyType = $this->keyType instanceof MixedType && $this->keyType->describe(VerbosityLevel::precise()) === 'mixed';
		$isMixedItemType = $this->itemType instanceof MixedType && $this->itemType->describe(VerbosityLevel::precise()) === 'mixed';
		if ($isMixedKeyType) {
			if ($isMixedItemType) {
				return 'iterable';
			}

			return sprintf('iterable<%s>', $this->itemType->describe($level));
		}

		return sprintf('iterable<%s, %s>', $this->keyType->describe($level), $this->itemType->describe($level));
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		if ($this->getIterableKeyType()->isSuperTypeOf($offsetType)->no()) {
			return TrinaryLogic::createNo();
		}

		return TrinaryLogic::createMaybe();
	}

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toAbsoluteNumber(): Type
	{
		return new ErrorType();
	}

	public function toString(): Type
	{
		return new ErrorType();
	}

	public function toInteger(): Type
	{
		return new ErrorType();
	}

	public function toFloat(): Type
	{
		return new ErrorType();
	}

	public function toArray(): Type
	{
		return new ArrayType($this->keyType, $this->getItemType());
	}

	public function toArrayKey(): Type
	{
		return new ErrorType();
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
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
		return $this->keyType;
	}

	public function getFirstIterableKeyType(): Type
	{
		return $this->keyType;
	}

	public function getLastIterableKeyType(): Type
	{
		return $this->keyType;
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

	public function isNull(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getConstantScalarTypes(): array
	{
		return [];
	}

	public function getConstantScalarValues(): array
	{
		return [];
	}

	public function isTrue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFalse(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isBoolean(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFloat(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInteger(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
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

	public function isClassStringType(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getClassStringObjectType(): Type
	{
		return new ErrorType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return new ObjectWithoutClassType();
	}

	public function isVoid(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return new BooleanType();
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if (!$receivedType->isIterable()->yes()) {
			return TemplateTypeMap::createEmpty();
		}

		$keyTypeMap = $this->getIterableKeyType()->inferTemplateTypes($receivedType->getIterableKeyType());
		$valueTypeMap = $this->getIterableValueType()->inferTemplateTypes($receivedType->getIterableValueType());

		return $keyTypeMap->union($valueTypeMap);
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());

		return array_merge(
			$this->getIterableKeyType()->getReferencedTemplateTypes($variance),
			$this->getIterableValueType()->getReferencedTemplateTypes($variance),
		);
	}

	public function traverse(callable $cb): Type
	{
		$keyType = $cb($this->keyType);
		$itemType = $cb($this->itemType);

		if ($keyType !== $this->keyType || $itemType !== $this->itemType) {
			return new self($keyType, $itemType);
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		$keyType = $cb($this->keyType, $right->getIterableKeyType());
		$itemType = $cb($this->itemType, $right->getIterableValueType());

		if ($keyType !== $this->keyType || $itemType !== $this->itemType) {
			return new self($keyType, $itemType);
		}

		return $this;
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		$arrayType = new ArrayType(new MixedType(), new MixedType());
		if ($typeToRemove->isSuperTypeOf($arrayType)->yes()) {
			return new GenericObjectType(Traversable::class, [
				$this->getIterableKeyType(),
				$this->getIterableValueType(),
			]);
		}

		$traversableType = new ObjectType(Traversable::class);
		if ($typeToRemove->isSuperTypeOf($traversableType)->yes()) {
			return new ArrayType($this->getIterableKeyType(), $this->getIterableValueType());
		}

		return null;
	}

	public function exponentiate(Type $exponent): Type
	{
		return new ErrorType();
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function toPhpDocNode(): TypeNode
	{
		$isMixedKeyType = $this->keyType instanceof MixedType && $this->keyType->describe(VerbosityLevel::precise()) === 'mixed';
		$isMixedItemType = $this->itemType instanceof MixedType && $this->itemType->describe(VerbosityLevel::precise()) === 'mixed';

		if ($isMixedKeyType) {
			if ($isMixedItemType) {
				return new IdentifierTypeNode('iterable');
			}

			return new GenericTypeNode(
				new IdentifierTypeNode('iterable'),
				[
					$this->itemType->toPhpDocNode(),
				],
			);
		}

		return new GenericTypeNode(
			new IdentifierTypeNode('iterable'),
			[
				$this->keyType->toPhpDocNode(),
				$this->itemType->toPhpDocNode(),
			],
		);
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['keyType'], $properties['itemType']);
	}

}
