<?php

namespace Bug6701;

class Foo
{

	public function doFoo(int $i)
	{
		$a = function ( string $test = null ): string {
			return $test ?? '';
		};
		$a(null);
		$a($i);

		$b = fn ( string $test = null ): string => $test ?? '';
		$b(null);
		$b($i);

		$c = function ( ?string $test = null ): string {
			return $test ?? '';
		};
		$c(null);
		$c($i);
	}

}
