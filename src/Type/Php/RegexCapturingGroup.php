<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

class RegexCapturingGroup
{

	private function __construct(
		private ?string $name,
		private bool $inAlternation,
		private bool $inOptionalQuantification,
	)
	{
	}

	public static function unnamed(bool $inAlternation, bool $inOptionalQuantification): self
	{
		return new self(null, $inAlternation, $inOptionalQuantification);
	}

	public static function named(string $name, bool $inAlternation, bool $inOptionalQuantification): self
	{
		return new self($name, $inAlternation, $inOptionalQuantification);
	}

	public function isOptional(): bool
	{
		return $this->inAlternation || $this->inOptionalQuantification;
	}

	public function inAlternation(): bool
	{
		return $this->inAlternation;
	}

	/** @phpstan-assert-if-true !null $this->getName() */
	public function isNamed(): bool
	{
		return $this->name !== null;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

}
