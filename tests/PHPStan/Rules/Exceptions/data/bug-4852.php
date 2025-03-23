<?php

namespace Bug4852;

use ArrayAccess;
use Exception;

final class DefinitelyThrows implements ArrayAccess
{
	public function offsetExists ($offset) {}
	public function offsetGet ($offset) {}

	/**
	 * @throws Exception
	 */
	public function offsetSet ($offset , $value) {
		throw new Exception();
	}
	public function offsetUnset ($offset) {}
}

final class MaybeThrows1 implements ArrayAccess
{
	public function offsetExists ($offset) {}
	public function offsetGet ($offset) {}
	public function offsetSet ($offset , $value) {
		throw new Exception();
	}
	public function offsetUnset ($offset) {}
}

class MaybeThrows2 {}

final class DefinitelyNoThrows implements ArrayAccess
{
	public function offsetExists ($offset) {}
	public function offsetGet ($offset) {}

	/**
	 * @throws void
	 */
	public function offsetSet ($offset , $value) {}
	public function offsetUnset ($offset) {}
}

$foo = new DefinitelyThrows();
try {
	$foo[] = 'value';
} catch (Exception $e) {
	// not dead
}

$bar = new MaybeThrows1();
try {
	$bar[] = 'value';
} catch (Exception $e) {
	// not dead
}

$buz = new MaybeThrows2();
try {
	$buz[] = 'value';
} catch (Exception $e) {
	// dead because $buz cannot be a subclass
}

function (MaybeThrows2 $buz): void {
	try {
		$buz[] = 'value';
	} catch (Exception $e) {
		// not dead
	}
};

$baz = new DefinitelyNoThrows();
try {
	$baz[] = 'value';
} catch (Exception $e) {
	// dead
}

$array = [];
try {
	$array[] = 'value';
} catch (Exception $e) {
	// dead
}

