<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class NativeParameterReflection implements ParameterReflection
{

	public function __construct(
		private string $name,
		private bool $optional,
		private Type $type,
		private PassedByReference $passedByReference,
		private bool $variadic,
		private ?Type $defaultValue,
	)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function isOptional(): bool
	{
		return $this->optional;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function passedByReference(): PassedByReference
	{
		return $this->passedByReference;
	}

	public function isVariadic(): bool
	{
		return $this->variadic;
	}

	public function getDefaultValue(): ?Type
	{
		return $this->defaultValue;
	}

	public function union(ParameterReflection $other): self
	{
		return new self(
			$this->name,
			$this->optional && $other->isOptional(),
			TypeCombinator::union($this->type, $other->getType()),
			$this->passedByReference->combine($other->passedByReference()),
			$this->variadic && $other->isVariadic(),
			$this->optional && $other->isOptional() ? $this->defaultValue : null,
		);
	}

}
