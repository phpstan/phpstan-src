<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Dummy;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

class DummyConstantReflection implements ConstantReflection
{

	private string $name;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function getDeclaringClass(): ClassReflection
	{
		$reflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		return $reflectionProvider->getClass(\stdClass::class);
	}

	public function getFileName(): ?string
	{
		return null;
	}

	public function isStatic(): bool
	{
		return true;
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

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		// so that Scope::getTypeFromValue() returns mixed
		return new \stdClass();
	}

	public function getValueType(): Type
	{
		return new MixedType();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getDocComment(): ?string
	{
		return null;
	}

}
