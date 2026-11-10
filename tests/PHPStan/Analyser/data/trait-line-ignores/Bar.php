<?php declare(strict_types = 1);

namespace TraitLineIgnores;

class Bar
{

	use UnusedParametersTrait;
	use PropertyTrait;
	use ConstantConditionTrait;

	public string $prop = '';

	public int $otherProp = 1;

	public function doBar(): bool
	{
		return true;
	}

}
