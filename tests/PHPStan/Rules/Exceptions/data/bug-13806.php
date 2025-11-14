<?php

namespace Bug13806;

function doFoo(MyString $myVariable): void
{
	try {
		(string) $myVariable;
	} catch (\InvalidArgumentException) {
		// Reported as dead catch, even though the `__toString()` method
		// in `$myVariable` might throw an exception.
	}
}

class MyString {
	/** @throws \InvalidArgumentException */
	public function __toString() {
		throw new \InvalidArgumentException();
	}
}
