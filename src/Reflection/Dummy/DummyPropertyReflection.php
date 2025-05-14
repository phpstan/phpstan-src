<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Dummy;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use stdClass;

final class DummyPropertyReflection implements ExtendedPropertyReflection
{

	public function __construct(private string $name)
	{
	}

	public function getName(): string
	{
		return $this->name;
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

	public function hasPhpDocType(): bool
	{
		return false;
	}

	public function getPhpDocType(): Type
	{
		return new MixedType();
	}

	public function hasNativeType(): bool
	{
		return false;
	}

	public function getNativeType(): Type
	{
		return new MixedType();
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

	public function isAbstract(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isVirtual(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function hasHook(string $hookType): bool
	{
		return false;
	}

	public function getHook(string $hookType): ExtendedMethodReflection
	{
		throw new ShouldNotHappenException();
	}

	public function isProtectedSet(): bool
	{
		return false;
	}

	public function isPrivateSet(): bool
	{
		return false;
	}

	public function getAttributes(): array
	{
		return [];
	}

}
