<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Type\BooleanType;
use PHPStan\Type\Type;

trait NonLooseComparableTrait
{

	public function looseCompare(Type $type): BooleanType
	{
		return new BooleanType();
	}

}
