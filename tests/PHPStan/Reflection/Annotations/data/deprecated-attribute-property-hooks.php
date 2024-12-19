<?php // lint >= 8.4

namespace DeprecatedAttributePropertyHooks;

use Deprecated;

class Foo
{

	public int $i {
		get {
			return 1;
		}
	}

	public int $j {
		#[Deprecated]
		get {
			return 1;
		}
	}

	public int $k {
		#[Deprecated('msg')]
		get {
			return 1;
		}
	}

	public int $l {
		#[Deprecated(since: '1.0', message: 'msg2')]
		get {
			return 1;
		}
	}

	public int $m {
		#[Deprecated(message: __FUNCTION__ . '+' . __METHOD__ . '+' . __PROPERTY__)]
		get {
			return 1;
		}
	}

}
