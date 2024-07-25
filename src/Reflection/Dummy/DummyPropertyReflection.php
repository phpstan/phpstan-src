<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Dummy;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use stdClass;

final class DummyPropertyReflection implements PropertyReflection
{

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

	public function getReadableType(): Type
	{
		return new MixedType();
	}

	public function getWritableType(): Type
	{
		return new MixedType();
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		return true;
	}

	public function isReadable(): bool
	{
		return true;
	}

	public function isWritable(): bool
	{
		return true;
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
