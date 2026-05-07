<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantStringType;
use function sprintf;

/** @api */
class ThisType extends StaticType
{

	/**
	 * @api
	 */
	public function __construct(
		ClassReflection $classReflection,
		?Type $subtractedType = null,
	)
	{
		parent::__construct($classReflection, $subtractedType);
	}

	public function changeBaseClass(ClassReflection $classReflection): StaticType
	{
		return new self($classReflection, $this->getSubtractedType());
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('$this(%s)', $this->getStaticObjectType()->describe($level));
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			return $this->getStaticObjectType()->isSuperTypeOf($type);
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		$parent = new parent($this->getClassReflection(), $this->getSubtractedType());

		return $parent->isSuperTypeOf($type)->and(IsSuperTypeOfResult::createMaybe());
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		$type = parent::changeSubtractedType($subtractedType);
		if ($type instanceof parent) {
			return new self($type->getClassReflection(), $subtractedType);
		}

		return $type;
	}

	public function traverse(callable $cb): Type
	{
		$subtractedType = $this->getSubtractedType() !== null ? $cb($this->getSubtractedType()) : null;

		if ($subtractedType !== $this->getSubtractedType()) {
			return new self(
				$this->getClassReflection(),
				$subtractedType,
			);
		}

		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if ($this->getSubtractedType() === null) {
			return $this;
		}

		return new self($this->getClassReflection());
	}

	public function toPhpDocNode(): TypeNode
	{
		return new ThisTypeNode();
	}

	public function toClassConstantType(ReflectionProvider $reflectionProvider): Type
	{
		// `$this` in a `final` class is pinned to that one class, so
		// `$this::class` collapses to its literal name. For non-final
		// classes `$this` could still be a subclass, so fall back to the
		// `class-string<$this>` projection from the parent.
		$reflection = $this->getClassReflection();
		if ($reflection->isFinalByKeyword()) {
			return new ConstantStringType($reflection->getName(), true);
		}

		return parent::toClassConstantType($reflectionProvider);
	}

}
