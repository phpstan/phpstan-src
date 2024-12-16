<?php // lint >= 8.4

namespace AssignHookedProperties;

class Foo
{

	public int $i {
		/** @param array<string>|int $val */
		set (array|int $val) {
			$this->i = $val; // only int allowed
		}
	}

	public int $j {
		/** @param array<string>|int $val */
		set (array|int $val) {
			$this->i = $val; // this is okay - hook called
			$this->j = $val; // only int allowed
		}
	}

	public function doFoo(): void
	{
		$this->i = ['foo']; // okay
		$this->i = 1; // okay
		$this->i = [1]; // not okay
	}

}
