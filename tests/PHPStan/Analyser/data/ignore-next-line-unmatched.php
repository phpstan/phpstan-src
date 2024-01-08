<?php

namespace IgnoreNextLineUnmatched;

class Foo
{

	public function doFoo(): void
	{
		/** @phpstan-ignore-next-line */
		succ(); // reported as unmatched

		/**
		 * @phpstan-ignore-next-line
		 * @var int
		 */
		succ(); // not reported as unmatched because phpstan-ignore-next-line is not last
	}

}
