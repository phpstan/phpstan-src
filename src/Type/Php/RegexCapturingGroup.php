<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

class RegexCapturingGroup
{

	private int $id;

	private static int $idGenerator = 1;

	private function __construct(
		private ?string $name,
		private bool $inAlternation,
		private bool $inOptionalQuantification,
		private ?RegexCapturingGroup $parent,
	)
	{
		$this->id = self::$idGenerator;
		self::$idGenerator++;
	}

	public static function unnamed(bool $inAlternation, bool $inOptionalQuantification, ?RegexCapturingGroup $parent): self
	{
		return new self(null, $inAlternation, $inOptionalQuantification, $parent);
	}

	public static function named(string $name, bool $inAlternation, bool $inOptionalQuantification, ?RegexCapturingGroup $parent): self
	{
		return new self($name, $inAlternation, $inOptionalQuantification, $parent);
	}

	public function removeOptionalQualification(): void
	{
		$this->inOptionalQuantification = false;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function isOptional(): bool
	{
		return $this->inAlternation
			|| $this->inOptionalQuantification
			|| ($this->parent !== null && $this->parent->isOptional());
	}

	public function inAlternation(): bool
	{
		return $this->inAlternation;
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
