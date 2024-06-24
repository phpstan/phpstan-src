<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

class RegexCapturingGroup
{

	private function __construct(private ?string $name, private bool $optional)
	{
	}

	public static function unnamed(bool $optional): self
	{
		return new self(null, $optional);
	}

	public static function named(string $name, bool $optional): self
	{
		return new self($name, $optional);
	}

	public function isOptional(): bool
	{
		return $this->optional;
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
