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

		/**
		 * @phpstan-ignore-next-line
		 */
		fail();
		fail(); // reported

		/**
		 * @noinspection PhpStrictTypeCheckingInspection
		 * @phpstan-ignore-next-line
		 */
		fail(); // not reported because the ignore tag is valid

		/**
		 * @phpstan-ignore-next-line Some very loooooooooooooooooooooooooooooooooooooooooooon
		 *                           coooooooooooooooooooooooooooooooooooooooooooooooooomment
		 *                           on many lines.
		 */
		fail(); // not reported because the ignore tag is valid

		/**
		 * @phpstan-ignore-next-line
		 * @var int
		 */
		fail(); // reported becase ignore tag in PHPDoc is not last
	}

}
