<?php // lint >= 8.4

namespace MatchExprPropertyHooks;

use UnhandledMatchError;

class Foo
{

	/** @var 1|2|3 */
	public int $i {
		get {
			return match ($this->i) {
				1 => 'foo',
				2 => 'bar',
			};
		}
	}

	/**
	 * @var 1|2|3
	 */
	public int $j {
		/** @throws UnhandledMatchError */
		get {
			return match ($this->j) {
				1 => 10,
				2 => 20,
			};
		}
	}

}
