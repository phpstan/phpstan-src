<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Php\BuiltinMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class NativeMethodReflection implements MethodReflection
{

	/** @var \PHPStan\Reflection\ReflectionProvider */
	private $reflectionProvider;

	/** @var \PHPStan\Reflection\ClassReflection */
	private $declaringClass;

	/** @var BuiltinMethodReflection */
	private $reflection;

	/** @var \PHPStan\Reflection\ParametersAcceptor[] */
	private $variants;

	/** @var TrinaryLogic */
	private $hasSideEffects;

	/** @var string|null */
	private $stubPhpDocString;

	/**
	 * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider
	 * @param \PHPStan\Reflection\ClassReflection $declaringClass
	 * @param BuiltinMethodReflection $reflection
	 * @param \PHPStan\Reflection\ParametersAcceptor[] $variants
	 * @param TrinaryLogic $hasSideEffects
	 * @param string|null $stubPhpDocString
	 */
	public function __construct(
		ReflectionProvider $reflectionProvider,
		ClassReflection $declaringClass,
		BuiltinMethodReflection $reflection,
		array $variants,
		TrinaryLogic $hasSideEffects,
		?string $stubPhpDocString
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->declaringClass = $declaringClass;
		$this->reflection = $reflection;
		$this->variants = $variants;
		$this->hasSideEffects = $hasSideEffects;
		$this->stubPhpDocString = $stubPhpDocString;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return $this->reflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->reflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->reflection->isPublic();
	}

	public function getPrototype(): ClassMemberReflection
	{
		try {
			$prototypeMethod = $this->reflection->getPrototype();
			$prototypeDeclaringClass = $this->reflectionProvider->getClass($prototypeMethod->getDeclaringClass()->getName());

			return new MethodPrototypeReflection(
				$prototypeDeclaringClass,
				$prototypeMethod->isStatic(),
				$prototypeMethod->isPrivate(),
				$prototypeMethod->isPublic(),
				$prototypeMethod->isAbstract()
			);
		} catch (\ReflectionException $e) {
			return $this;
		}
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	/**
	 * @return \PHPStan\Reflection\ParametersAcceptor[]
	 */
	public function getVariants(): array
	{
		return $this->variants;
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->reflection->isDeprecated();
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getThrowType(): ?Type
	{
		return null;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return $this->hasSideEffects;
	}

	public function getDocComment(): ?string
	{
		if ($this->stubPhpDocString !== null) {
			return $this->stubPhpDocString;
		}

		return $this->reflection->getDocComment();
	}

}
