<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

class FoundPropertyReflection implements PropertyReflection
{

	private PropertyReflection $originalPropertyReflection;

	private Type $readableType;

	private Type $writableType;

	public function __construct(
		PropertyReflection $originalPropertyReflection,
		Type $readableType,
		Type $writableType
	)
	{
		$this->originalPropertyReflection = $originalPropertyReflection;
		$this->readableType = $readableType;
		$this->writableType = $writableType;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->originalPropertyReflection->getDeclaringClass();
	}

	public function isStatic(): bool
	{
		return $this->originalPropertyReflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->originalPropertyReflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->originalPropertyReflection->isPublic();
	}

	public function getDocComment(): ?string
	{
		return $this->originalPropertyReflection->getDocComment();
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
		return $this->originalPropertyReflection->canChangeTypeAfterAssignment();
	}

	public function isReadable(): bool
	{
		return $this->originalPropertyReflection->isReadable();
	}

	public function isWritable(): bool
	{
		return $this->originalPropertyReflection->isWritable();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->originalPropertyReflection->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->originalPropertyReflection->getDeprecatedDescription();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->originalPropertyReflection->isInternal();
	}

	public function isNative(): bool
	{
		$reflection = $this->originalPropertyReflection;
		if ($reflection instanceof ResolvedPropertyReflection) {
			$reflection = $reflection->getOriginalReflection();
		}

		return $reflection instanceof PhpPropertyReflection;
	}

	public function getNativeType(): ?Type
	{
		$reflection = $this->originalPropertyReflection;
		if ($reflection instanceof ResolvedPropertyReflection) {
			$reflection = $reflection->getOriginalReflection();
		}

		if (!$reflection instanceof PhpPropertyReflection) {
			return null;
		}

		return $reflection->getNativeType();
	}

}
