<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstFetchNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeUnsealedTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function count;
use function in_array;
use function sprintf;

/**
 * Array shape whose keys are not known yet because at least one of them is a
 * template type: `array{TKey: int}`. Once the template types are resolved the
 * shape collapses into a ConstantArrayType (or an ErrorType when the resolved
 * key cannot be used as an array key at all).
 */
final class LateResolvableArrayShapeType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	/**
	 * @param list<array{Type|null, Type, bool}> $items keyType (null for auto-index), valueType, optional
	 * @param array{Type, Type}|null $unsealed
	 * @param ArrayShapeNode::KIND_* $kind
	 */
	private function __construct(
		private array $items,
		private ?array $unsealed,
		private string $kind,
		private bool $hasCallableItem,
	)
	{
	}

	/**
	 * Builds the array shape type, keeping it late-resolvable only for as long
	 * as some of its keys still contain template types.
	 *
	 * @param list<array{Type|null, Type, bool}> $items keyType (null for auto-index), valueType, optional
	 * @param array{Type, Type}|null $unsealed
	 * @param ArrayShapeNode::KIND_* $kind
	 */
	public static function create(array $items, ?array $unsealed, string $kind, bool $hasCallableItem): Type
	{
		$self = new self($items, $unsealed, $kind, $hasCallableItem);
		if ($self->isResolvable()) {
			return $self->resolve();
		}

		return $self;
	}

	public function getReferencedClasses(): array
	{
		$classes = [];
		foreach ($this->items as [$keyType, $valueType]) {
			if ($keyType !== null) {
				$classes = array_merge($classes, $keyType->getReferencedClasses());
			}
			$classes = array_merge($classes, $valueType->getReferencedClasses());
		}

		if ($this->unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $this->unsealed;
			$classes = array_merge($classes, $unsealedKeyType->getReferencedClasses(), $unsealedValueType->getReferencedClasses());
		}

		return $classes;
	}

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getObjectClassReflections(): array
	{
		return [];
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$references = [];
		foreach ($this->items as [$keyType, $valueType]) {
			if ($keyType !== null) {
				$references = array_merge($references, $keyType->getReferencedTemplateTypes($positionVariance));
			}
			$references = array_merge($references, $valueType->getReferencedTemplateTypes($positionVariance));
		}

		if ($this->unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $this->unsealed;
			$references = array_merge(
				$references,
				$unsealedKeyType->getReferencedTemplateTypes($positionVariance),
				$unsealedValueType->getReferencedTemplateTypes($positionVariance),
			);
		}

		return $references;
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if ($this->kind !== $type->kind || count($this->items) !== count($type->items)) {
			return false;
		}

		foreach ($this->items as $i => [$keyType, $valueType, $optional]) {
			[$otherKeyType, $otherValueType, $otherOptional] = $type->items[$i];
			if ($optional !== $otherOptional) {
				return false;
			}
			if (($keyType === null) !== ($otherKeyType === null)) {
				return false;
			}
			if ($keyType !== null && $otherKeyType !== null && !$keyType->equals($otherKeyType)) {
				return false;
			}
			if (!$valueType->equals($otherValueType)) {
				return false;
			}
		}

		if (($this->unsealed === null) !== ($type->unsealed === null)) {
			return false;
		}

		if ($this->unsealed !== null && $type->unsealed !== null) {
			return $this->unsealed[0]->equals($type->unsealed[0])
				&& $this->unsealed[1]->equals($type->unsealed[1]);
		}

		return true;
	}

	public function describe(VerbosityLevel $level): string
	{
		if ($this->isResolvable()) {
			return $this->resolve()->describe($level);
		}

		return (new Printer())->print($this->toPhpDocNode());
	}

	public function isResolvable(): bool
	{
		foreach ($this->items as [$keyType]) {
			if ($keyType === null) {
				continue;
			}

			if (TypeUtils::containsTemplateType($keyType)) {
				return false;
			}
		}

		if ($this->unsealed !== null && TypeUtils::containsTemplateType($this->unsealed[0])) {
			return false;
		}

		return true;
	}

	protected function getResult(): Type
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		$builder->disableArrayDegradation();
		if ($this->hasCallableItem) {
			$builder->disableClosureDegradation();
		}

		$explicitKeyValues = [];
		foreach ($this->items as [$keyType, $valueType, $optional]) {
			if ($keyType !== null) {
				if ($keyType instanceof ErrorType) {
					return $keyType;
				}

				if (TypeUtils::containsTemplateType($keyType)) {
					// The template type is never going to be resolved here, so
					// the shape degrades into a general array. Its key has to be
					// a valid array key though, which a template type bound by
					// e.g. mixed is not.
					$keyType = TemplateTypeHelper::resolveToBounds($keyType);
				}

				$arrayKeyType = $keyType->toArrayKey();
				if ($arrayKeyType instanceof ErrorType) {
					return new ErrorType(sprintf(
						'Type %s cannot be used as an array shape key.',
						$keyType->describe(VerbosityLevel::typeOnly()),
					));
				}

				$keyType = $arrayKeyType;
				if ($keyType instanceof ConstantIntegerType || $keyType instanceof ConstantStringType) {
					$explicitKeyValues[] = $keyType->getValue();
				}
			}

			$builder->setOffsetValueType($keyType, $valueType, $optional);
		}

		$isList = in_array($this->kind, [
			ArrayShapeNode::KIND_LIST,
			ArrayShapeNode::KIND_NON_EMPTY_LIST,
		], true);

		if ($this->unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $this->unsealed;
			$unsealedKeyFiniteTypes = $unsealedKeyType->getFiniteTypes();
			if (count($unsealedKeyFiniteTypes) > 0) {
				foreach ($unsealedKeyFiniteTypes as $unsealedKeyFiniteType) {
					// Explicit keys own their slot — the unsealed extras
					// describe entries at keys NOT in the explicit set.
					if (
						($unsealedKeyFiniteType instanceof ConstantIntegerType || $unsealedKeyFiniteType instanceof ConstantStringType)
						&& in_array($unsealedKeyFiniteType->getValue(), $explicitKeyValues, true)
					) {
						continue;
					}
					$builder->setOffsetValueType($unsealedKeyFiniteType, $unsealedValueType, true);
				}
			} else {
				$builder->makeUnsealed($unsealedKeyType, $unsealedValueType);
			}
		}

		$arrayType = $builder->getArray();

		$accessories = [];
		if ($isList) {
			$accessories[] = new AccessoryArrayListType();
		}

		if (in_array($this->kind, [
			ArrayShapeNode::KIND_NON_EMPTY_ARRAY,
			ArrayShapeNode::KIND_NON_EMPTY_LIST,
		], true)) {
			$accessories[] = new NonEmptyArrayType();
		}

		if (count($accessories) > 0) {
			return TypeCombinator::intersect($arrayType, ...$accessories);
		}

		return $arrayType;
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$stillOriginal = true;
		$items = [];
		foreach ($this->items as [$keyType, $valueType, $optional]) {
			$newKeyType = $keyType !== null ? $cb($keyType) : null;
			$newValueType = $cb($valueType);
			if ($newKeyType !== $keyType || $newValueType !== $valueType) {
				$stillOriginal = false;
			}

			$items[] = [$newKeyType, $newValueType, $optional];
		}

		$unsealed = $this->unsealed;
		if ($unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $unsealed;
			$newUnsealedKeyType = $cb($unsealedKeyType);
			$newUnsealedValueType = $cb($unsealedValueType);
			if ($newUnsealedKeyType !== $unsealedKeyType || $newUnsealedValueType !== $unsealedValueType) {
				$stillOriginal = false;
				$unsealed = [$newUnsealedKeyType, $newUnsealedValueType];
			}
		}

		if ($stillOriginal) {
			return $this;
		}

		return self::create($items, $unsealed, $this->kind, $this->hasCallableItem);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof self) {
			return $this;
		}

		if (count($this->items) !== count($right->items)) {
			return $this;
		}

		$stillOriginal = true;
		$items = [];
		foreach ($this->items as $i => [$keyType, $valueType, $optional]) {
			[$rightKeyType, $rightValueType] = $right->items[$i];
			$newKeyType = $keyType !== null && $rightKeyType !== null ? $cb($keyType, $rightKeyType) : $keyType;
			$newValueType = $cb($valueType, $rightValueType);
			if ($newKeyType !== $keyType || $newValueType !== $valueType) {
				$stillOriginal = false;
			}

			$items[] = [$newKeyType, $newValueType, $optional];
		}

		$unsealed = $this->unsealed;
		if ($unsealed !== null && $right->unsealed !== null) {
			[$unsealedKeyType, $unsealedValueType] = $unsealed;
			$newUnsealedKeyType = $cb($unsealedKeyType, $right->unsealed[0]);
			$newUnsealedValueType = $cb($unsealedValueType, $right->unsealed[1]);
			if ($newUnsealedKeyType !== $unsealedKeyType || $newUnsealedValueType !== $unsealedValueType) {
				$stillOriginal = false;
				$unsealed = [$newUnsealedKeyType, $newUnsealedValueType];
			}
		}

		if ($stillOriginal) {
			return $this;
		}

		return self::create($items, $unsealed, $this->kind, $this->hasCallableItem);
	}

	public function toPhpDocNode(): TypeNode
	{
		$items = [];
		foreach ($this->items as [$keyType, $valueType, $optional]) {
			$items[] = new ArrayShapeItemNode(
				$keyType !== null ? self::keyNameNode($keyType) : null,
				$optional,
				$valueType->toPhpDocNode(),
			);
		}

		if ($this->unsealed === null) {
			return ArrayShapeNode::createSealed($items, $this->kind);
		}

		[$unsealedKeyType, $unsealedValueType] = $this->unsealed;

		return ArrayShapeNode::createUnsealed($items, new ArrayShapeUnsealedTypeNode(
			$unsealedKeyType->toPhpDocNode(),
			$unsealedValueType->toPhpDocNode(),
		), $this->kind);
	}

	/**
	 * @return ConstExprIntegerNode|ConstExprStringNode|ConstFetchNode|IdentifierTypeNode
	 */
	private static function keyNameNode(Type $keyType)
	{
		$node = $keyType->toPhpDocNode();
		if ($node instanceof IdentifierTypeNode) {
			return $node;
		}

		if ($node instanceof ConstTypeNode) {
			$constExpr = $node->constExpr;
			if (
				$constExpr instanceof ConstExprIntegerNode
				|| $constExpr instanceof ConstExprStringNode
				|| $constExpr instanceof ConstFetchNode
			) {
				return $constExpr;
			}
		}

		return new IdentifierTypeNode($keyType->describe(VerbosityLevel::precise()));
	}

}
