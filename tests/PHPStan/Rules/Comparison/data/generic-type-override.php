<?php

namespace GenericTypeOverride;

use function PHPStan\Testing\assertType;

class Test {
	public function doFoo() {
		$foo = $this->createGenericFoo();
		assertType('Foo<int>', $foo);

		// $foo generic will be overridden via MethodTypeSpecifyingExtension
		$foo->setFetchMode();
		assertType('Foo<Bar>', $foo);
	}

	/**
	 * @return Foo<int>
	 */
	public function createGenericFoo() {

	}
}


/**
 * @template T
 */
class Foo
{
	public function setFetchMode() {

	}
}


class Bar
{
}
