<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterDefaultValue;

class Foo
{

	/**
	 * @template T
	 * @param T $value
	 * @return T
	 */
	public function identity($value = 1)
	{
		return $value;
	}

}
