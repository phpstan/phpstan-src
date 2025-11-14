<?php

namespace Bug13806;

function doFoo(MyString $myVariable, MyStringVoid $string, $mixed): void
{
	try {
		(string) $myVariable;
	} catch (\InvalidArgumentException $e) {
		// Reported as dead catch, even though the `__toString()` method
		// in `$myVariable` might throw an exception.
	}

	try {
		(string) $string;
	} catch (\InvalidArgumentException $e) {
	}

	try {
		(string) $mixed;
	} catch (\InvalidArgumentException $e) {
	}
}

class MyString {
	/** @throws \InvalidArgumentException */
	public function __toString() {
		throw new \InvalidArgumentException();
	}
}

class MyStringVoid {
	/** @throws void */
	public function __toString()
	{
		throw new \InvalidArgumentException();
	}
}
