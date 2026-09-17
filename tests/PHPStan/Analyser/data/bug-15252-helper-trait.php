<?php declare(strict_types = 1);

namespace Bug15252;

use Ns\Bar;
use Ns\Bar2 as Bar;

trait HelperTrait
{

	public function name(): string
	{
		return 'trait';
	}

}
