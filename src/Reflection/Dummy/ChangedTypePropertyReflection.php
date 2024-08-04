<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Dummy;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\WrapperPropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

final class ChangedTypePropertyReflection implements WrapperPropertyReflection
{

	public function __construct(private ClassReflection $declaringClass, private ExtendedPropertyReflection $reflection, private Type $readableType, private Type $writableType)
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

	public function getDocComment(): ?string
	{
		return $this->reflection->getDocComment();
	}

	public function getReadableType(): Type
	{
		return $this->readableType;
	}

	public function getWritableType(): Type
	{
		return $this->writableType;
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		return $this->reflection->canChangeTypeAfterAssignment();
	}

	public function isReadable(): bool
	{
		return $this->reflection->isReadable();
	}

	public function isWritable(): bool
	{
		return $this->reflection->isWritable();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->reflection->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->reflection->getDeprecatedDescription();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->reflection->isInternal();
	}

	public function getOriginalReflection(): ExtendedPropertyReflection
	{
		return $this->reflection;
	}

}
