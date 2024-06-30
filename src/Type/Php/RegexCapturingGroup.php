<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

class RegexCapturingGroup
{

	private function __construct(
		private ?string $name,
		private bool $inAlternation,
		private bool $inOptionalQuantification,
		private RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	)
	{
	}

	public static function unnamed(
		bool $inAlternation,
		bool $inOptionalQuantification,
		RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	): self
	{
		return new self(null, $inAlternation, $inOptionalQuantification, $parent);
	}

	public static function named(
		string $name,
		bool $inAlternation,
		bool $inOptionalQuantification,
		RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	): self
	{
		return new self($name, $inAlternation, $inOptionalQuantification, $parent);
	}

	public function removeOptionalQualification(): void
	{
		$this->inOptionalQuantification = false;
	}

	public function isOptional(): bool
	{
		return $this->inAlternation
			|| $this->inOptionalQuantification
			|| ($this->parent !== null && $this->parent->isOptional());
	}

	public function isTopLevel(): bool
	{
		return $this->parent === null;
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
