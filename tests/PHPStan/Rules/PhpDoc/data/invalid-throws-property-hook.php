<?php // lint >= 8.4

namespace InvalidThrowsPropertyHook;

class Foo
{

	public int $i {
		/** @throws \InvalidArgumentException */
		get {
			return 1;
		}
	}

	public int $j {
		/** @throws \DateTimeImmutable */
		get {
			return 1;
		}
	}

}
