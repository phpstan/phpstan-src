<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

/**
 * @api
 * @final
 */
class EnumCaseReflection
{

	public function __construct(private ClassReflection $declaringEnum, private string $name, private ?Type $backingValueType)
	{
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
