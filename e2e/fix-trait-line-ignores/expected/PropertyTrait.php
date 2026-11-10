<?php declare(strict_types = 1);

namespace TraitLineIgnores;

trait PropertyTrait
{

	public function getProp(): int
	{
		return $this->prop; // @phpstan-ignore return.type (legacy), property.notFound (legacy)
	}

}
