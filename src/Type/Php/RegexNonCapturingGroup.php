<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

class RegexNonCapturingGroup
{

	public function __construct(
		private ?int $alternationId,
		private bool $inOptionalQuantification,
		private RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
		private bool $resetGroupCounter,
	)
	{
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

	public function resetsGroupCounter(): bool
	{
		return $this->resetGroupCounter;
	}

}
