<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;

class AnnotationPropertyReflection implements PropertyReflection
{

	public function __construct(
		private ClassReflection $declaringClass,
		private ?Type $readableType = null,
		private ?Type $writableType = null,
	)
	{
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
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
		return $this->readableType ?? new VoidType();
	}

	public function getWritableType(): Type
	{
		return $this->writableType ?? new VoidType();
	}

	public function canChangeTypeAfterAssignment(): bool
	{
		return true;
	}

	public function isReadable(): bool
	{
		return $this->readableType !== null;
	}

	public function isWritable(): bool
	{
		return $this->writableType !== null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDocComment(): ?string
	{
		return null;
	}

}
