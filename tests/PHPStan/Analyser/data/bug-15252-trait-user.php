<?php declare(strict_types = 1);

namespace Bug15252;

class TraitUser
{

	use HelperTrait;

	public function run(): string
	{
		return $this->name();
	}

}
