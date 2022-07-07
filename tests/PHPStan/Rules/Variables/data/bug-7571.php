<?php

namespace Bug7571;

class C {
	public function m(): void {
		if (isset($this))
			print "Called as static!\n";
		else
			print "Called as non-static!\n";
	}
}
