<?php

namespace ReturnsByReference;

function foo() {}

function &refFoo() {}

class X {
	function foo() {}

	function &refFoo() {}
}

class SubX extends X {
	function &subRefFoo() {}

}
