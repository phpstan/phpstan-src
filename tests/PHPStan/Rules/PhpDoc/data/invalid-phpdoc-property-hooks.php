<?php // lint >= 8.4

namespace InvalidPhpDocPropertyHooks;

class Foo
{

	public int $i {
		/** @return Test( */
		get {

		}
	}

}
