<?php declare(strict_types = 1);

namespace E2eMemoizedRequire;

use E2eDepInternal\SomeInterface;

final class Handler implements SomeInterface
{

	public function handle(): string
	{
		return 'ok';
	}

}
