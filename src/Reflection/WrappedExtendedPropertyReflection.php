<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

final class WrappedExtendedPropertyReflection implements ExtendedPropertyReflection
{

	public function __construct(private PropertyReflection $property)
	{
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

}
