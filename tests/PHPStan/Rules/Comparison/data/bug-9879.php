<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug9879;

final class A {
	public function test(): void
	{
		for($idx = 0; $idx < 6; $idx += 1) {
			match($idx % 3) {
				0 => 1,
				1 => 2,
				2 => 0,
			};
		}

	}

}
