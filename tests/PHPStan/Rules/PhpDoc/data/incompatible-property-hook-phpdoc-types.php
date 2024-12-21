<?php // lint >= 8.4

namespace IncompatiblePropertyHookPhpDocTypes;

class Foo
{

	public int $i {
		/** @return string */
		get {
			return $this->i;
		}
	}

	public int $j {
		/** @return string */
		set {
			$this->j = 1;
		}
	}

	public int $k {
		/**
		 * @param string $value
		 * @param-out int $value
		 */
		set {
			$this->k = 1;
		}
	}

	public int $l {
		/** @param \stdClass&\Exception $value */
		set {

		}
	}

	public \Exception $m {
		/** @param \Exception<int> $value */
		set {

		}
	}

}

/** @template T */
class GenericFoo
{

	public int $n {
		/** @param int|callable<T>(T): T $value */
		set (int|callable $value) {

		}
	}

	public int $o {
		/** @param int|callable<\stdClass>(T): T $value */
		set (int|callable $value) {

		}
	}

}
