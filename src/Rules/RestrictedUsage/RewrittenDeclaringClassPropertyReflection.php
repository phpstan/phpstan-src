<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

final class RewrittenDeclaringClassPropertyReflection implements ExtendedPropertyReflection
{

	public function __construct(
		private ClassReflection $declaringClass,
		private ExtendedPropertyReflection $propertyReflection,
	)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return $this->propertyReflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->propertyReflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->propertyReflection->isPublic();
	}

	public function getDocComment(): ?string
	{
		return $this->propertyReflection->getDocComment();
	}

	public function getName(): string
	{
		return $this->propertyReflection->getName();
	}

	public function hasPhpDocType(): bool
	{
		return $this->propertyReflection->hasPhpDocType();
	}

	public function getPhpDocType(): Type
	{
		return $this->propertyReflection->getPhpDocType();
	}

	public function hasNativeType(): bool
	{
		return $this->propertyReflection->hasNativeType();
	}

	public function getNativeType(): Type
	{
		return $this->propertyReflection->getNativeType();
	}

	public function isAbstract(): TrinaryLogic
	{
		return $this->propertyReflection->isAbstract();
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return $this->propertyReflection->isFinalByKeyword();
	}

	public function isFinal(): TrinaryLogic
	{
		return $this->propertyReflection->isFinal();
	}

	public function isVirtual(): TrinaryLogic
	{
		return $this->propertyReflection->isVirtual();
	}

	public function hasHook(string $hookType): bool
	{
		return $this->propertyReflection->hasHook($hookType);
	}

	public function getHook(string $hookType): ExtendedMethodReflection
	{
		return $this->propertyReflection->getHook($hookType);
	}

	public function isProtectedSet(): bool
	{
		return $this->propertyReflection->isProtectedSet();
	}

	public function isPrivateSet(): bool
	{
		return $this->propertyReflection->isPrivateSet();
	}

	public function getAttributes(): array
	{
		return $this->propertyReflection->getAttributes();
	}

	public function getReadableType(): Type
	{
		return $this->propertyReflection->getReadableType();
	}

	public function getWritableType(): Type
	{
		return $this->propertyReflection->getWritableType();
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		return $this->propertyReflection->canChangeTypeAfterAssignment();
	}

	public function isReadable(): bool
	{
		return $this->propertyReflection->isReadable();
	}

	public function isWritable(): bool
	{
		return $this->propertyReflection->isWritable();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->propertyReflection->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->propertyReflection->getDeprecatedDescription();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->propertyReflection->isInternal();
	}

	public function isDummy(): TrinaryLogic
	{
		return $this->propertyReflection->isDummy();
	}

}
