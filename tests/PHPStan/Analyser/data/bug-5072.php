<?php

namespace Bug5072;


use function PHPStan\Testing\assertType;

class HelloWorld
{

	public function doFoo(): void
	{
		assertType('1', max(1, -3));
	}

	/**
	 * @param array<string, mixed> $params
	 */
	public function incorrect(array $params): void
	{
		$page = isset($params['page']) ? intval($params['page']) : 1;
		assertType('int<1, max>', max(1, $page));
	}

	public function incorrectWithConstant(): void
	{
		assertType('int<1, max>', max(1, PHP_INT_MAX));
	}
}
