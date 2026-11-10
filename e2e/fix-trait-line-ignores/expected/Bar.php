<?php declare(strict_types = 1);

namespace TraitLineIgnores;

class Bar implements Checked
{

	use UnusedParametersTrait;
	use PropertyTrait;
	use ConstantConditionTrait;
	use CheckedTrait;

	public string $prop = '';

	public function doBar(): bool
	{
		return true;
	}

}
