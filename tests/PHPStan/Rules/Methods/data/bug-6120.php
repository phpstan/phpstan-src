<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug6120;

class Clazz
{

	public int $foo = 0;

	public function bar(?Clazz $clazz): void
	{
		$result = $clazz?->foo;
		if ($result !== null) {
			$clazz->bar(null);
		}
	}

}
