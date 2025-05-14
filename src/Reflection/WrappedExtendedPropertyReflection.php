<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

final class WrappedExtendedPropertyReflection implements ExtendedPropertyReflection
{

	public function __construct(private string $name, private PropertyReflection $property)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->property->getDeclaringClass();
	}

	public function isStatic(): bool
	{
		return $this->property->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->property->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->property->isPublic();
	}

	public function getDocComment(): ?string
	{
		return $this->property->getDocComment();
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
		return $this->property->getReadableType();
	}

	public function getWritableType(): Type
	{
		return $this->property->getWritableType();
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		return $this->property->canChangeTypeAfterAssignment();
	}

	public function isReadable(): bool
	{
		return $this->property->isReadable();
	}

	public function isWritable(): bool
	{
		return $this->property->isWritable();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->property->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->property->getDeprecatedDescription();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->property->isInternal();
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
