<?php declare(strict_types = 1);

namespace TraitsIgnoreErrorsAnonymous;

use Countable;

function createFirst(): Countable
{
	return new class implements Countable {

		use IgnoreErrorsAnonymousTrait;

		public function count(): int
		{
			return 1;
		}

	};
}

function createSecond(): Countable
{
	return new class implements Countable {

		use IgnoreErrorsAnonymousTrait;

		public function count(): int
		{
			return 2;
		}

	};
}
