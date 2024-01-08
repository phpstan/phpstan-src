<?php

namespace IgnoreNextLine;

class Foo
{

	public function doFoo(): void
	{
		fail(); // reported

		// @phpstan-ignore-next-line
		fail();

		/* @phpstan-ignore-next-line */
		fail();

		/** @phpstan-ignore-next-line */
		fail();
		fail(); // reported

		// @phpstan-ignore-next-line
		if (fail()) {
			fail(); // reported
		}
	}

}
