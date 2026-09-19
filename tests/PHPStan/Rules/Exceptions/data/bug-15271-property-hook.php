<?php // lint >= 8.4

namespace Bug15271PropertyHook;

class Foo
{

	public int $i {
		get {
			get_called_class();

			throw new \TypeError();
		}
	}

	public int $j {
		set {
			throw new \ValueError();
		}
	}

	public int $k {
		get {
			throw new \Exception();
		}
	}

}
