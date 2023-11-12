<?php

namespace Bug9615;

class Filter extends \RecursiveFilterIterator {
	#[\ReturnTypeWillChange]
	public function accept() { return true; }

	#[\ReturnTypeWillChange]
	public function getChildren() { return null; }
}

class ThisShouldBeFine extends Filter {
	public function accept() { return true; }
	public function getChildren() { return null; }
}

class ExpectComplaintsHere extends \RecursiveFilterIterator {
	public function accept() { return true; }
	public function getChildren() { return null; }
}
