<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

class RegexCapturingGroup
{

	private int $id;

	private bool $forceNonOptional = false;

	private static int $idGenerator = 100;

	private function __construct(
		private ?string $name,
		private ?int $alternationId,
		private bool $inOptionalQuantification,
		private RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	)
	{
		$this->id = self::$idGenerator;
		self::$idGenerator++;
	}

	public static function unnamed(
		?int $alternationId,
		bool $inOptionalQuantification,
		RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	): self
	{
		return new self(null, $alternationId, $inOptionalQuantification, $parent);
	}

	public static function named(
		string $name,
		?int $alternationId,
		bool $inOptionalQuantification,
		RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	): self
	{
		return new self($name, $alternationId, $inOptionalQuantification, $parent);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function forceNonOptional(): void
	{
		$this->forceNonOptional = true;
	}

	public function restoreNonOptional(): void
	{
		$this->forceNonOptional = false;
	}

	/** @phpstan-assert-if-true !null $this->getAlternationId() */
	public function inAlternation(): bool
	{
		return $this->alternationId !== null;
	}

	public function getAlternationId(): ?int
	{
		return $this->alternationId;
	}

	public function isOptional(): bool
	{
		if ($this->forceNonOptional) {
			return false;
		}

		return $this->inAlternation()
			|| $this->inOptionalQuantification
			|| $this->parent !== null && $this->parent->isOptional();
	}

	public function inOptionalAlternation(): bool
	{
		if (!$this->inAlternation()) {
			return false;
		}

		$parent = $this->parent;
		while ($parent !== null && $parent->getAlternationId() === $this->getAlternationId()) {
			if (!$parent instanceof RegexNonCapturingGroup) {
				return false;
			}
			$parent = $parent->getParent();
		}
		return $parent !== null && $parent->isOptional();
	}

	public function isTopLevel(): bool
	{
		return $this->parent === null
			|| $this->parent instanceof RegexNonCapturingGroup && $this->parent->isTopLevel();
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
