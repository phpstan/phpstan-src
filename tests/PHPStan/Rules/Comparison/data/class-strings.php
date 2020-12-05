<?php

namespace ClassStrings;

use stdClass;

class IsSubclassOfClassStrings
{
	/**
	 * @param class-string $classString
	 * @return bool
	 */
	public function doFoo(string $classString): bool
	{
		return is_subclass_of(stdClass::class, $classString);
	}

	/**
	 * @param class-string<object> $classString
	 * @return bool
	 */
	public function doBar(string $classString): bool
	{
		return is_subclass_of(stdClass::class, $classString);
	}
}
