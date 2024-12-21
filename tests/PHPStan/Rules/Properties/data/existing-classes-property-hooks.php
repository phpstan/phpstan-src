<?php // lint >= 8.4

namespace ExistingClassesPropertyHooks;

class Foo
{

	public int $i {
		set (Nonexistent $v) {

		}
	}

	public \stdClass $j {
		set (\stdClass&\Exception $v) {

		}
	}

	/** @var Undefined */
	public $k {
		get {

		}
	}

	/** @var Undefined */
	public $l {
		set {

		}
	}

}
