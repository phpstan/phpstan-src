<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Dummy;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use stdClass;

class DummyMethodReflection implements ExtendedMethodReflection
{

	public function __construct(private string $name)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		return $reflectionProvider->getClass(stdClass::class);
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

	public function getName(): string
	{
		return $this->name;
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this;
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getVariants(): array
	{
		return [
			new TrivialParametersAcceptor(),
		];
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getThrowType(): ?Type
	{
		return null;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function getAsserts(): Assertions
	{
		return Assertions::createEmpty();
	}

	public function getSelfOutType(): ?Type
	{
		return null;
	}

	public function returnsByReference(): ?bool
	{
		return null;
	}

}
