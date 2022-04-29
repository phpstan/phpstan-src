<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionIntersectionType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionUnionType;
use PHPStan\TrinaryLogic;
use ReflectionException;

class FakeBuiltinMethodReflection implements BuiltinMethodReflection
{

	public function __construct(
		private string $methodName,
		private ReflectionClass|ReflectionEnum $declaringClass,
	)
	{
	}

	public function getName(): string
	{
		return $this->methodName;
	}

	public function getReflection(): ?ReflectionMethod
	{
		return null;
	}

	public function getFileName(): ?string
	{
		return null;
	}

	public function getDeclaringClass(): ReflectionClass|ReflectionEnum
	{
		return $this->declaringClass;
	}

	public function getStartLine(): ?int
	{
		return null;
	}

	public function getEndLine(): ?int
	{
		return null;
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function isStatic(): bool
	{
		return false;
	}

	public function isPrivate(): bool
	{
		return false;
	}

	public function isPublic(): bool
	{
		return true;
	}

	public function getPrototype(): BuiltinMethodReflection
	{
		throw new ReflectionException();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isVariadic(): bool
	{
		return false;
	}

	public function isFinal(): bool
	{
		return false;
	}

	public function isInternal(): bool
	{
		return false;
	}

	public function isAbstract(): bool
	{
		return false;
	}

	public function getReturnType(): ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null
	{
		return null;
	}

	public function getTentativeReturnType(): ReflectionIntersectionType|ReflectionNamedType|ReflectionUnionType|null
	{
		return null;
	}

	/**
	 * @return ReflectionParameter[]
	 */
	public function getParameters(): array
	{
		return [];
	}

}
