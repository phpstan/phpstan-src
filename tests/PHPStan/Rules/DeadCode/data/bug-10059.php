<?php // lint >= 8.1

namespace Bug10059;

use DateTimeImmutable;

class Foo
{
	public function __construct(
		private readonly DateTimeImmutable $startDateTime
	) {
	}

	public function bar(): void
	{
		declare(ticks=5) {
			echo $this->startDateTime->format('Y-m-d H:i:s.u'), PHP_EOL;
		}
	}
}
