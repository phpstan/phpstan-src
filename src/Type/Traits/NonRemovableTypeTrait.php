<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Type\Type;

trait NonRemovableTypeTrait
{

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return null;
	}

}
