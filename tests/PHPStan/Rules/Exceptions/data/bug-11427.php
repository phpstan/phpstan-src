<?php

namespace Bug11427;

/** @implements \ArrayAccess<int, int> */
class C implements \ArrayAccess {
	#[\ReturnTypeWillChange]
	public function offsetExists($offset) {
		throw new \Exception("exists");
	}

	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		throw new \Exception("get");
	}

	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value) {
		throw new \Exception("set");
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset($offset) {
		throw new \Exception("unset");
	}
}

function test(C $c): void {
	try {
		$x = isset($c[1]);
	} catch (\Exception $e) {
		// offsetExists can throw
	}

	try {
		$x = $c[1];
	} catch (\Exception $e) {
		// offsetGet can throw
	}

	try {
		$c[1] = 1;
	} catch (\Exception $e) {
		// offsetSet can throw
	}

	try {
		unset($c[1]);
	} catch (\Exception $e) {
		// offsetUnset can throw
	}
}

/**
 * Union type where isArray() returns maybe and isSuperTypeOf(ArrayAccess) returns maybe.
 * This ensures the conditions in NodeScopeResolver are tested with types
 * that distinguish !->yes() from ->no() and !->no() from ->yes().
 *
 * @param array<int, int>|C $c
 */
function testArrayOrArrayAccess($c): void {
	try {
		$x = isset($c[1]);
	} catch (\Exception $e) {
		// offsetExists can throw when $c is C
	}

	try {
		$x = $c[1];
	} catch (\Exception $e) {
		// offsetGet can throw when $c is C
	}

	try {
		$c[1] = 1;
	} catch (\Exception $e) {
		// offsetSet can throw when $c is C
	}

	try {
		unset($c[1]);
	} catch (\Exception $e) {
		// offsetUnset can throw when $c is C
	}
}
