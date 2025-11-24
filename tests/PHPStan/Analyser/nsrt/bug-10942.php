<?php declare(strict_types = 1);

namespace Bug10942;

class A
{

	/**
	 * @param string|($operator is 'in' ? int : never) $sqlRight
	 */
	protected function _renderConditionBinary(string $operator, string $sqlLeft, $sqlRight): string
	{
		return 'x';
	}

}

class B extends A
{

	#[\Override]
	protected function _renderConditionBinary(string $operator, string $sqlLeft, $sqlRight): string
	{
		return 'y';
	}

}
