<?php

namespace Bug5259Types;

use DateTime;
use DateTimeImmutable;
use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(\DateTimeInterface $dtin): void
	{
		if ($dtin instanceof \DateTimeImmutable) {
			return;
		}

		assertType(DateTime::class, $dtin);
	}

	public function doBar(\DateTimeInterface $dtin): void
	{
		if ($dtin instanceof \DateTime) {
			return;
		}

		assertType(DateTimeImmutable::class, $dtin);
	}

}
