<?php

namespace IgnoreNextLineUnmatched;

class Foo
{

	public function doFoo(): void
	{
		/** @phpstan-ignore-next-line */
		succ(); // reported as unmatched
	}

}
