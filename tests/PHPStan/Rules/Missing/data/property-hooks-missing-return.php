<?php // lint >= 8.4

namespace PropertyHooksMissingReturn;

class Foo
{

	public int $i {
		get {
			if (rand(0, 1)) {

			} else {
				return 1;
			}
		}

		set {
			// set hook returns void
		}
	}

	public int $j {
		get {

		}
	}

	public int $ok {
		get {
			return $this->ok + 1;
		}
	}

}
