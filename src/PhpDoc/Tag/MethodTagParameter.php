<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Tag;

use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;

/**
 * @api
 * @final
 */
class MethodTagParameter
{

	public function __construct(
		private Type $type,
		private PassedByReference $passedByReference,
		private bool $isOptional,
		private bool $isVariadic,
		private ?Type $defaultValue,
	)
	{
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function passedByReference(): PassedByReference
	{
		return $this->passedByReference;
	}

	public function isOptional(): bool
	{
		return $this->isOptional;
	}

	public function isVariadic(): bool
	{
		return $this->isVariadic;
	}

	public function getDefaultValue(): ?Type
	{
		return $this->defaultValue;
	}

}
