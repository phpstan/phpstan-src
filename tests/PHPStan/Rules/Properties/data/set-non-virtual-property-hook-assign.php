<?php // lint >= 8.4

namespace SetNonVirtualPropertyHookAssign;

class Foo
{

	public int $i {
		get {
			return 1;
		}
		set {
			// virtual property
			$this->j = $value;
		}
	}

	public int $j;

	public int $k {
		get {
			return $this->k + 1;
		}
		set {
			// backed property, missing assign should be reported
			$this->j = $value;
		}
	}

	public int $k2 {
		get {
			return $this->k2 + 1;
		}
		set {
			// backed property, missing assign should be reported
			if (rand(0, 1)) {
				return;
			}

			$this->k2 = $value;
		}
	}

	public int $k3 {
		get {
			return $this->k3 + 1;
		}
		set {
			// backed property, always assigned (or throws)
			if (rand(0, 1)) {
				throw new \Exception();
			}

			$this->k3 = $value;
		}
	}

	public int $k4 {
		get {
			return $this->k4 + 1;
		}
		set {
			// backed property, always assigned
			$this->k4 = $value;
		}
	}

}
