<?php declare(strict_types = 1);

namespace BaselineIntegration;

use function array_key_first;

class A
{

	/**
	 * @return array<array<int>>
	 */
	public function doFoo(): array
	{
		return [['foo']];
	}

	public function doBar(): void
	{
		array_key_first();
	}

}
