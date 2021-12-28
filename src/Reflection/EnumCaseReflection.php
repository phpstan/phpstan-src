<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

/** @api */
class EnumCaseReflection
{

	private ClassReflection $declaringEnum;

	private string $name;

	private ?Type $backingValueType;

	public function __construct(ClassReflection $declaringEnum, string $name, ?Type $backingValueType)
	{
		$this->declaringEnum = $declaringEnum;
		$this->name = $name;
		$this->backingValueType = $backingValueType;
	}

	public function getDeclaringEnum(): ClassReflection
	{
		return $this->declaringEnum;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getBackingValueType(): ?Type
	{
		return $this->backingValueType;
	}

}
