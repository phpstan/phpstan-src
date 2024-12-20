<?php // lint >= 8.4

namespace MissingExceptionPropertyHookThrows;

class Foo
{

	public int $i {
		/** @throws \InvalidArgumentException */
		get {
			throw new \InvalidArgumentException(); // ok
		}
	}

	public int $j {
		/** @throws \LogicException */
		set {
			throw new \InvalidArgumentException(); // ok
		}
	}

	public int $k {
		/** @throws \RuntimeException */
		get {
			throw new \InvalidArgumentException(); // error
		}
	}

	public int $l {
		/** @throws \RuntimeException */
		set {
			throw new \InvalidArgumentException(); // error
		}
	}

	public int $m {
		get {
			throw new \InvalidArgumentException(); // error
		}
	}

}
