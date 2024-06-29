<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

class RegexCapturingGroup
{
	private int $id;

	static private int $idGenerator = 1;

	private function __construct(
		private ?string $name,
		private bool $inAlternation,
		private bool $inOptionalQuantification,
		private bool $isTopLevel,
	)
	{
		$this->id = self::$idGenerator;
		self::$idGenerator++;
	}

	public static function unnamed(bool $inAlternation, bool $inOptionalQuantification, bool $isTopLevel): self
	{
		return new self(null, $inAlternation, $inOptionalQuantification, $isTopLevel);
	}

	public static function named(string $name, bool $inAlternation, bool $inOptionalQuantification, bool $isTopLevel): self
	{
		return new self($name, $inAlternation, $inOptionalQuantification, $isTopLevel);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function isOptional(): bool
	{
		return $this->inAlternation || $this->inOptionalQuantification;
	}

	public function inAlternation(): bool
	{
		return $this->inAlternation;
	}

	public function isTopLevel(): bool
	{
		return $this->isTopLevel;
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
