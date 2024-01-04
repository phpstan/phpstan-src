<?php

namespace IgnoreNextLineLegacy;

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

		/*
		 * @phpstan-ignore-next-line
		 */
		fail();

		/**
		 * @phpstan-ignore-next-line
		 *
		 * This is the legacy behaviour, the next line is meant as next-non-comment-line
		 */
		fail();
		fail(); // reported

		// @phpstan-ignore-next-line
		if (fail()) {
			fail(); // reported
		}
	}

}
