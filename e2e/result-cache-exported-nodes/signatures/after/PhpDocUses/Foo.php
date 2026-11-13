<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\PhpDocUses;

use ResultCacheE2EExportedNodes\PhpDocUses\StringBox as Box;

class Foo
{

	/**
	 * @return Box
	 */
	public function box()
	{
		return new Box();
	}

}
