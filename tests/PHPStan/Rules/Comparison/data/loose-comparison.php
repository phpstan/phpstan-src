<?php

namespace ConstantLooseComparison;

class Foo
{

	public function doFoo(string $s, string $i): void
	{
		if ($s == $i) {

		}
		if ($s != $i) {

		}
		if (0 == "0") {

		}

		if (0 == "1") {

		}
	}

}
