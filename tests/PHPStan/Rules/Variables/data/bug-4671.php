<?php

namespace Bug4671;

class Foo
{

	/**
	 * @param array<string, string> $strings
	 */
	public function doFoo(int $input, array $strings): void
	{
		if (isset($strings[(string) $input])) {
		}
	}

}
