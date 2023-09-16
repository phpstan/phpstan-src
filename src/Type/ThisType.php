<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
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

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return $this->getStaticObjectType()->isSuperTypeOf($type);
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		$parent = new parent($this->getClassReflection(), $this->getSubtractedType());

		return $parent->isSuperTypeOf($type)->and(TrinaryLogic::createMaybe());
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

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		if ($reflectionProvider->hasClass($properties['baseClass'])) {
			return new self($reflectionProvider->getClass($properties['baseClass']), $properties['subtractedType'] ?? null);
		}

		return new ErrorType();
	}

}
