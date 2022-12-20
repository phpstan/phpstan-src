<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
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

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('%s<%s>', parent::describe($level), $this->type->describe($level));
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		if ($type instanceof ConstantStringType) {
			if (!$type->isClassStringType()->yes()) {
				return TrinaryLogic::createNo();
			}

			$objectType = new ObjectType($type->getValue());
		} elseif ($type instanceof self) {
			$objectType = $type->type;
		} elseif ($type instanceof ClassStringType) {
			$objectType = new ObjectWithoutClassType();
		} elseif ($type instanceof StringType) {
			return TrinaryLogic::createMaybe();
		} else {
			return TrinaryLogic::createNo();
		}

		return $this->type->accepts($objectType, $strictTypes);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		if ($type instanceof ConstantStringType) {
			$genericType = $this->type;
			if ($genericType instanceof MixedType) {
				return TrinaryLogic::createYes();
			}

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

	public function tryRemove(Type $typeToRemove): ?Type
	{
		if ($typeToRemove instanceof ConstantStringType && $typeToRemove->isClassStringType()->yes()) {
			$generic = $this->getGenericType();

			if ($generic instanceof TypeWithClassName) {
				$classReflection = $generic->getClassReflection();
				if (
					$classReflection !== null
					&& $classReflection->isFinal()
					&& $generic->getClassName() === $typeToRemove->getValue()
				) {
					return new NeverType();
				}
			}
		}

		return parent::tryRemove($typeToRemove);
	}

}
