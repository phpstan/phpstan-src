<?php declare(strict_types = 1);

namespace IgnoreErrorsPartialAnalysis;

class UsesSharedTraitWithOwnError
{

	use SharedTrait;

	public function doOwn(): int
	{
		return 'own';
	}

}
