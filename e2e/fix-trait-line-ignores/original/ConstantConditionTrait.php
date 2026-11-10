<?php declare(strict_types = 1);

namespace TraitLineIgnores;

trait ConstantConditionTrait
{

	public function doFoo(int $i): void
	{
		// @phpstan-ignore-next-line legacy
		if (is_int($i) && $this->doBar()) {
		}
	}

}
