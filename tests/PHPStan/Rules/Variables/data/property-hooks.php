<?php // lint >= 8.4

namespace PropertyHooksVariables;

class Foo
{

	public int $i {
		set {
			$this->i = $value + 10;
		}
	}

	public int $iErr {
		set {
			$this->iErr = $val + 10;
		}
	}

	public int $j {
		set (int $val) {
			$this->j = $val + 10;
		}
	}

	public int $jErr {
		set (int $val) {
			$this->jErr = $value + 10;
		}
	}

}


class FooShort
{

	public int $i {
		set => $value + 10;
	}

	public int $iErr {
		set => $val + 10;
	}

	public int $j {
		set (int $val) => $val + 10;
	}

	public int $jErr {
		set (int $val) => $value + 10;
	}

}
