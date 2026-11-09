<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_keys;
use function array_merge;
use function count;
use function sprintf;

/**
 * Object shape whose property names are not known yet because at least one of
 * them is a template type: `object{TKey: int}`. Once the template types are
 * resolved the shape collapses into an ObjectShapeType (or an ErrorType when
 * the resolved key cannot be a property name at all). Written shapes without
 * template keys go through it too and resolve right away.
 */
final class LateResolvableObjectShapeType implements CompoundType, LateResolvableType
{

	use LateResolvableTypeTrait;
	use NonGeneralizableTypeTrait;

	/**
	 * @param list<array{Type, Type, bool}> $items keyType, valueType, optional
	 */
	private function __construct(private array $items)
	{
	}

	/**
	 * Builds the object shape type, keeping it late-resolvable only for as long
	 * as some of its keys still contain template types.
	 *
	 * @param list<array{Type, Type, bool}> $items keyType, valueType, optional
	 */
	public static function create(array $items): Type
	{
		$self = new self($items);
		if ($self->isResolvable()) {
			return $self->resolve();
		}

		return $self;
	}

	public function getReferencedClasses(): array
	{
		$classes = [];
		foreach ($this->items as [$keyType, $valueType]) {
			$classes = array_merge($classes, $keyType->getReferencedClasses(), $valueType->getReferencedClasses());
		}

		return $classes;
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$references = [];
		foreach ($this->items as [$keyType, $valueType]) {
			$references = array_merge(
				$references,
				$keyType->getReferencedTemplateTypes($positionVariance),
				$valueType->getReferencedTemplateTypes($positionVariance),
			);
		}

		return $references;
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if (count($this->items) !== count($type->items)) {
			return false;
		}

		foreach ($this->items as $i => [$keyType, $valueType, $optional]) {
			[$otherKeyType, $otherValueType, $otherOptional] = $type->items[$i];
			if ($optional !== $otherOptional) {
				return false;
			}
			if (!$keyType->equals($otherKeyType)) {
				return false;
			}
			if (!$valueType->equals($otherValueType)) {
				return false;
			}
		}

		return true;
	}

	public function describe(VerbosityLevel $level): string
	{
		// a shape that can be resolved never stays late-resolvable, see create()
		return TypeNodeDescriber::describe($this->toPhpDocNode());
	}

	public function isResolvable(): bool
	{
		foreach ($this->items as [$keyType]) {
			if (TypeUtils::containsTemplateType($keyType)) {
				return false;
			}
		}

		return true;
	}

	protected function getResult(): Type
	{
		/** @var array<int|string, Type> $properties */
		$properties = [];
		/** @var array<int|string, true> $writtenOptional */
		$writtenOptional = [];
		/** @var array<int|string, true> $uncertain */
		$uncertain = [];
		$hasUnknownName = false;

		foreach ($this->items as [$keyType, $valueType, $optional]) {
			if (TypeUtils::containsTemplateType($keyType)) {
				// at this point the template types are never going to be
				// substituted anymore, so the key degrades to their bounds
				$keyType = TemplateTypeHelper::resolveToBounds($keyType);
			}

			if ($keyType instanceof ErrorType) {
				return $keyType;
			}

			$isString = $keyType->isString();
			if ($isString->no()) {
				return new ErrorType(sprintf(
					'Type %s cannot be used as an object shape key.',
					$keyType->describe(VerbosityLevel::typeOnly()),
				));
			}

			if ($isString->maybe()) {
				// only the string part can name a property
				$keyType = TypeCombinator::intersect($keyType, new StringType());
			}

			$constantStrings = $keyType->getConstantStrings();
			if (count($constantStrings) === 0) {
				// the property name is not known; object shapes allow
				// properties they do not list, so it is left out
				$hasUnknownName = true;
				continue;
			}

			if (count($constantStrings) === 1 && $isString->yes()) {
				// a known name: the later one wins, like in a written shape
				$propertyName = $constantStrings[0]->getValue();
				$properties[$propertyName] = $valueType;
				unset($uncertain[$propertyName]);
				if ($optional) {
					$writtenOptional[$propertyName] = true;
				}
				continue;
			}

			// any of several names: each of them only might be there
			foreach ($constantStrings as $constantString) {
				$propertyName = $constantString->getValue();
				if (isset($properties[$propertyName])) {
					$properties[$propertyName] = TypeCombinator::union($properties[$propertyName], $valueType);
					continue;
				}

				$properties[$propertyName] = $valueType;
				$uncertain[$propertyName] = true;
			}
		}

		if (count($properties) === 0 && $hasUnknownName) {
			return new ObjectWithoutClassType();
		}

		$optionalProperties = [];
		foreach (array_keys($properties) as $propertyName) {
			if (!isset($writtenOptional[$propertyName]) && !isset($uncertain[$propertyName])) {
				continue;
			}

			$optionalProperties[] = $propertyName;
		}

		return new ObjectShapeType($properties, $optionalProperties);
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): Type
	{
		$stillOriginal = true;
		$items = [];
		foreach ($this->items as [$keyType, $valueType, $optional]) {
			$newKeyType = $cb($keyType);
			$newValueType = $cb($valueType);
			if ($newKeyType !== $keyType || $newValueType !== $valueType) {
				$stillOriginal = false;
			}

			$items[] = [$newKeyType, $newValueType, $optional];
		}

		if ($stillOriginal) {
			return $this;
		}

		return self::create($items);
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
			$newKeyType = $cb($keyType, $rightKeyType);
			$newValueType = $cb($valueType, $rightValueType);
			if ($newKeyType !== $keyType || $newValueType !== $valueType) {
				$stillOriginal = false;
			}

			$items[] = [$newKeyType, $newValueType, $optional];
		}

		if ($stillOriginal) {
			return $this;
		}

		return self::create($items);
	}

	public function toPhpDocNode(): TypeNode
	{
		$items = [];
		foreach ($this->items as [$keyType, $valueType, $optional]) {
			$items[] = new ObjectShapeItemNode(
				self::keyNameNode($keyType),
				$optional,
				$valueType->toPhpDocNode(),
			);
		}

		return new ObjectShapeNode($items);
	}

	private static function keyNameNode(Type $keyType): ConstExprStringNode|IdentifierTypeNode
	{
		$node = $keyType->toPhpDocNode();
		if ($node instanceof IdentifierTypeNode) {
			return $node;
		}

		if ($node instanceof ConstTypeNode && $node->constExpr instanceof ConstExprStringNode) {
			if (ConstantArrayType::isValidIdentifier($node->constExpr->value)) {
				return new IdentifierTypeNode($node->constExpr->value);
			}

			return $node->constExpr;
		}

		return new IdentifierTypeNode($keyType->describe(VerbosityLevel::precise()));
	}

}
