<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\BuiltinMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VoidType;
use ReflectionException;
use function strtolower;

class NativeMethodReflection implements ExtendedMethodReflection
{

	/**
	 * @param ParametersAcceptorWithPhpDocs[] $variants
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassReflection $declaringClass,
		private BuiltinMethodReflection $reflection,
		private array $variants,
		private TrinaryLogic $hasSideEffects,
		private ?Type $throwType,
		private Assertions $assertions,
	)
	{
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

	public function isAbstract(): bool
	{
		return $this->reflection->isAbstract();
	}

	public function getPrototype(): ClassMemberReflection
	{
		try {
			$prototypeMethod = $this->reflection->getPrototype();
			$prototypeDeclaringClass = $this->reflectionProvider->getClass($prototypeMethod->getDeclaringClass()->getName());

			$tentativeReturnType = null;
			if ($prototypeMethod->getTentativeReturnType() !== null) {
				$tentativeReturnType = TypehintHelper::decideTypeFromReflection($prototypeMethod->getTentativeReturnType());
			}

			return new MethodPrototypeReflection(
				$prototypeMethod->getName(),
				$prototypeDeclaringClass,
				$prototypeMethod->isStatic(),
				$prototypeMethod->isPrivate(),
				$prototypeMethod->isPublic(),
				$prototypeMethod->isAbstract(),
				$prototypeMethod->isFinal(),
				$prototypeDeclaringClass->getNativeMethod($prototypeMethod->getName())->getVariants(),
				$tentativeReturnType,
			);
		} catch (ReflectionException) {
			return $this;
		}
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	/**
	 * @return ParametersAcceptorWithPhpDocs[]
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
		return TrinaryLogic::createFromBoolean($this->reflection->isFinal());
	}

	public function getThrowType(): ?Type
	{
		return $this->throwType;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		$name = strtolower($this->getName());
		$isVoid = $this->isVoid();
		if (
			$name !== '__construct'
			&& $isVoid
		) {
			return TrinaryLogic::createYes();
		}

		return $this->hasSideEffects;
	}

	private function isVoid(): bool
	{
		foreach ($this->variants as $variant) {
			if (!$variant->getReturnType() instanceof VoidType) {
				return false;
			}
		}

		return true;
	}

	public function getDocComment(): ?string
	{
		return $this->reflection->getDocComment();
	}

	public function getAsserts(): Assertions
	{
		return $this->assertions;
	}

}
