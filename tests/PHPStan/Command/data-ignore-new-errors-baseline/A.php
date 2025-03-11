<?php declare(strict_types = 1);

namespace BaselineIntegration;

use function array_key_first;

class A
{

	public function doBar(): void
	{
		array_key_first();
		array_key_first();
	}

}
