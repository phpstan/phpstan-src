<?php

namespace Bug12735;

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

	private function foo(DateTimeImmutable $a, DateTimeImmutable $b): void {}
}
