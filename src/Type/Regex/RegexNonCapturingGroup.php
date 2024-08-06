<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex;

class RegexNonCapturingGroup
{

	public function __construct(
		private ?RegexAlternation $alternation,
		private bool $inOptionalQuantification,
		private RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
		private bool $resetGroupCounter,
	)
	{
	}

	/** @phpstan-assert-if-true !null $this->getAlternationId() */
	public function inAlternation(): bool
	{
		return $this->alternation !== null;
	}

	public function getAlternationId(): ?int
	{
		if ($this->alternation === null) {
			return null;
		}

		return $this->alternation->getId();
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
