<?php

namespace Bug12735b;

use DateTimeImmutable;

class HelloWorld
{
	public function test(): void
	{
		$now = null;
		if (rand(0,1)) {
			$now = new DateTimeImmutable();
		}

		$this->foo(
			$now ??= new DateTimeImmutable(),
			$now,
		);

		$this->foo($now, $now);
	}

	private function foo(DateTimeImmutable $a, DateTimeImmutable $b): void {}
}
