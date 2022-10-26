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

class TraitX {
	use T;
}

trait T {
	function traitFoo() {}

	function &refTraitFoo() {}
}
