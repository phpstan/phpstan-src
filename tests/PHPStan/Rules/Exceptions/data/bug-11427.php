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

class D implements \ArrayAccess {
	/**
	 * @throws void
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists($offset) {
		throw new \Exception("exists");
	}

	/**
	 * @throws void
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		throw new \Exception("get");
	}

	/**
	 * @throws void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value) {
		throw new \Exception("set");
	}

	/**
	 * @throws void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset) {
		throw new \Exception("unset");
	}
}

function test2(D $c): void {
	try {
		$x = isset($c[1]);
	} catch (\Exception $e) {
		// offsetExists cannot throw
	}

	try {
		$x = $c[1];
	} catch (\Exception $e) {
		// offsetGet cannot throw
	}

	try {
		$c[1] = 1;
	} catch (\Exception $e) {
		// offsetSet cannot throw
	}

	try {
		unset($c[1]);
	} catch (\Exception $e) {
		// offsetUnset cannot throw
	}
}

/**
 * @param array<C> $arrayOfC
 */
function test3(array $arrayOfC): void
{
	try {
		$x = isset($arrayOfC[0][1]);
	} catch (\Exception $e) {
		// offsetExists can throw
	}

	try {
		$x = $arrayOfC[0][1];
	} catch (\Exception $e) {
		// offsetGet can throw
	}

	try {
		$arrayOfC[0][1] = 1;
	} catch (\Exception $e) {
		// offsetSet can throw
	}

	try {
		unset($arrayOfC[0][1]);
	} catch (\Exception $e) {
		// offsetUnset can throw
	}
}

/**
 * @param array<C> $arrayOfC
 */
function test4(array $arrayOfC): void {
	try {
		$x = isset($arrayOfC[0]);
	} catch (\Exception $e) {
		// offsetExists cannot throw
	}

	try {
		$x = $arrayOfC[0];
	} catch (\Exception $e) {
		// offsetGet cannot throw
	}

	/** @var C<int, int> $newC */
	$newC = new C();
	try {
		$arrayOfC[0] = $newC;
	} catch (\Exception $e) {
		// offsetSet cannot throw
	}

	try {
		unset($arrayOfC[0]);
	} catch (\Exception $e) {
		// offsetUnset cannot throw
	}
}
