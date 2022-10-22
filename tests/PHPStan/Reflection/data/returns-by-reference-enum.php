<?php // lint >= 8.1

namespace ReturnsByReference;

enum E {
	case E1;

	function enumFoo() {}

	function &refEnumFoo() {}
}
