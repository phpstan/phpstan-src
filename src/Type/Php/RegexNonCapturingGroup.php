<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

class RegexNonCapturingGroup
{

	private function __construct(
		private ?int $alternationId,
		private bool $inOptionalQuantification,
		private RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	)
	{
	}

	public static function create(
		?int $alternationId,
		bool $inOptionalQuantification,
		RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	): self
	{
		return new self($alternationId, $inOptionalQuantification, $parent);
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
		return $this->inAlternation()
			|| $this->inOptionalQuantification
			|| ($this->parent !== null && $this->parent->isOptional());
	}

	public function isTopLevel(): bool
	{
		return $this->parent === null
			|| $this->parent instanceof RegexNonCapturingGroup && $this->parent->isTopLevel();
	}

	public function getParent(): RegexCapturingGroup|RegexNonCapturingGroup|null
	{
		return $this->parent;
	}

}
