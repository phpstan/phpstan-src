<?php declare(strict_types = 1);

namespace Bug12735;

use DateTimeImmutable;

class HelloWorld
{
	public function test(): void
	{
		$this->foo(
			$now = new DateTimeImmutable(),
			$now,
		);

		$this->foo($now, $now);
	}

	public function test2(): void
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
