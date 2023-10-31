<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/** @api */
class GenericClassStringType extends ClassStringType
{

	/** @api */
	public function __construct(private Type $type)
	{
		parent::__construct();
	}

	public function getReferencedClasses(): array
	{
		return $this->type->getReferencedClasses();
	}

	public function getGenericType(): Type
	{
		return $this->type;
	}

	public function getClassStringObjectType(): Type
	{
		return $this->getGenericType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return $this->getClassStringObjectType();
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('%s<%s>', parent::describe($level), $this->type->describe($level));
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		if ($type instanceof ConstantStringType) {
			if (!$type->isClassStringType()->yes()) {
				return AcceptsResult::createNo();
			}

			$objectType = new ObjectType($type->getValue());
		} elseif ($type instanceof self) {
			$objectType = $type->type;
		} elseif ($type instanceof ClassStringType) {
			$objectType = new ObjectWithoutClassType();
		} elseif ($type instanceof StringType) {
			return AcceptsResult::createMaybe();
		} else {
			return AcceptsResult::createNo();
		}

		return $this->type->acceptsWithReason($objectType, $strictTypes);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if ($type instanceof ConstantStringType) {
			if ($type->isClassStringType()->no()) {
				return TrinaryLogic::createNo();
			}

			$genericType = $this->type;
			if ($genericType instanceof StaticType) {
				$genericType = $genericType->getStaticObjectType();
			}

			// We are transforming constant class-string to ObjectType. But we need to filter out
			// an uncertainty originating in possible ObjectType's class subtypes.
			$objectType = new ObjectType($type->getValue());

			// Do not use TemplateType's isSuperTypeOf handling directly because it takes ObjectType
			// uncertainty into account.
			if ($genericType instanceof TemplateType) {
				$isSuperType = $genericType->getBound()->isSuperTypeOf($objectType);
			} else {
				$isSuperType = $genericType->isSuperTypeOf($objectType);
			}

			if (!$type->isClassStringType()->yes()) {
				$isSuperType = $isSuperType->and(TrinaryLogic::createMaybe());
			}

			return $isSuperType;
		} elseif ($type instanceof self) {
			return $this->type->isSuperTypeOf($type->type);
		} elseif ($type instanceof StringType) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createNo();
	}

	public function traverse(callable $cb): Type
	{
		$newType = $cb($this->type);
		if ($newType === $this->type) {
			return $this;
		}

		return new self($newType);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		$newType = $cb($this->type, $right->getClassStringObjectType());
		if ($newType === $this->type) {
			return $this;
		}

		return new self($newType);
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if ($receivedType instanceof UnionType || $receivedType instanceof IntersectionType) {
			return $receivedType->inferTemplateTypesOn($this);
		}

		if ($receivedType instanceof ConstantStringType) {
			$typeToInfer = new ObjectType($receivedType->getValue());
		} elseif ($receivedType instanceof self) {
			$typeToInfer = $receivedType->type;
		} elseif ($receivedType->isClassStringType()->yes()) {
			$typeToInfer = $this->type;
			if ($typeToInfer instanceof TemplateType) {
				$typeToInfer = $typeToInfer->getBound();
			}

			$typeToInfer = TypeCombinator::intersect($typeToInfer, new ObjectWithoutClassType());
		} else {
			return TemplateTypeMap::createEmpty();
		}

		return $this->type->inferTemplateTypes($typeToInfer);
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		$variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());

		return $this->type->getReferencedTemplateTypes($variance);
	}

	public function equals(Type $type): bool
	{
		if (!$type instanceof self) {
			return false;
		}

		if (!parent::equals($type)) {
			return false;
		}

		if (!$this->type->equals($type->type)) {
			return false;
		}

		return true;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self($properties['type']);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new GenericTypeNode(
			new IdentifierTypeNode('class-string'),
			[
				$this->type->toPhpDocNode(),
			],
		);
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove instanceof ConstantStringType && $typeToRemove->isClassStringType()->yes()) {
			$generic = $this->getGenericType();

			$genericObjectClassNames = $generic->getObjectClassNames();
			if (count($genericObjectClassNames) === 1) {
				$classReflection = ReflectionProviderStaticAccessor::getInstance()->getClass($genericObjectClassNames[0]);
				if ($classReflection->isFinal() && $genericObjectClassNames[0] === $typeToRemove->getValue()) {
					return new NeverType();
				}
			}
		}

		return parent::tryRemove($typeToRemove);
	}

}
