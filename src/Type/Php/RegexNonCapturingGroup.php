<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

class RegexNonCapturingGroup
{

	private function __construct(
		private bool $inOptionalQuantification,
		private RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	)
	{
	}

	public static function create(
		bool $inOptionalQuantification,
		RegexCapturingGroup|RegexNonCapturingGroup|null $parent,
	): self
	{
		return new self($inOptionalQuantification, $parent);
	}

	public function isOptional(): bool
	{
		return $this->inOptionalQuantification
			|| ($this->parent !== null && $this->parent->isOptional());
	}

}
