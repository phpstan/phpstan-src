<?php declare(strict_types = 1);

namespace TraitLineIgnores;

trait PropertyTrait
{

	public function getProp(): int
	{
		return $this->prop; // @phpstan-ignore-line legacy
	}

}
