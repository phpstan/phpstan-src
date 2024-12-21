<?php // lint >= 8.4

namespace InvalidPHPStanTagPropertyHooks;

class Foo
{

	public int $i {
		/** @phpstan-what what */
		get {

		}
	}

}
