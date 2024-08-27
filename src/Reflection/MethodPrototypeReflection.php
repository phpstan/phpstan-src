<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

final class MethodPrototypeReflection implements ClassMemberReflection
{

	/**
	 * @param ParametersAcceptor[] $variants
	 */
	public function __construct(
		private string $name,
		private ClassReflection $declaringClass,
		private bool $isStatic,
		private bool $isPrivate,
		private bool $isPublic,
		private bool $isAbstract,
		private bool $isFinal,
		private bool $isInternal,
		private array $variants,
		private ?Type $tentativeReturnType,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return $this->isStatic;
	}

	public function isPrivate(): bool
	{
		return $this->isPrivate;
	}

	public function isPublic(): bool
	{
		return $this->isPublic;
	}

	public function isAbstract(): bool
	{
		return $this->isAbstract;
	}

	public function isFinal(): bool
	{
		return $this->isFinal;
	}

	public function isInternal(): bool
	{
		return $this->isInternal;
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getVariants(): array
	{
		return $this->variants;
	}

	public function getTentativeReturnType(): ?Type
	{
		return $this->tentativeReturnType;
	}

}
