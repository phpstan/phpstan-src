<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

class MethodPrototypeReflection implements ClassMemberReflection
{

	private \PHPStan\Reflection\ClassReflection $declaringClass;

	private string $name;

	private bool $isStatic;

	private bool $isPrivate;

	private bool $isPublic;

	private bool $isAbstract;

	private bool $isFinal;

	public function __construct(
		string $name,
		ClassReflection $declaringClass,
		bool $isStatic,
		bool $isPrivate,
		bool $isPublic,
		bool $isAbstract,
		bool $isFinal
	)
	{
		$this->name = $name;
		$this->declaringClass = $declaringClass;
		$this->isStatic = $isStatic;
		$this->isPrivate = $isPrivate;
		$this->isPublic = $isPublic;
		$this->isAbstract = $isAbstract;
		$this->isFinal = $isFinal;
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

	public function getDocComment(): ?string
	{
		return null;
	}

}
