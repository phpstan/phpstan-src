<?php // lint >= 8.4

namespace GetNonVirtualPropertyHookRead;

class Foo
{

	public int $i {
		// backed, read and written
		get => $this->i + 1;
		set => $this->i + $value;
	}

	public int $j {
		// virtual
		get => 1;
		set {
			$this->a = $value;
		}
	}

	public int $k {
		// backed, not read
		get => 1;
		set => $value + 1;
	}

	public int $l {
		// backed, not read, long get
		get {
			return 1;
		}
		set => $value + 1;
	}

	public int $m {
		// it is okay to only read it sometimes
		get {
			if (rand(0, 1)) {
				return 1;
			}

			return $this->m;
		}
		set => $value + 1;
	}

}
