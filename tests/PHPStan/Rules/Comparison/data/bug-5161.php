<?php // lint >= 8.0

namespace Bug5161;

final class Log {

	public int $a;

	public function test(int $i): void
	{
		$this->a = match (true) {
			$i >= 30 => 30,
			$i >= 20 => 20,
			$i >= 10 => 10,
			default => 0,
		};

	}

}
