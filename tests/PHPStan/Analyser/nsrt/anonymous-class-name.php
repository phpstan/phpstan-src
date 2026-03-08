<?php

namespace AnonymousClassName;

use function PHPStan\Testing\assertType;

function () {
	$foo = new class () {

		/** @var Foo */
		public $fooProperty;

		/**
		 * @return Foo
		 */
		public function doFoo()
		{
			assertType('$this(AnonymousClassa438d668c1555ab44fca0ccbd64d84d8)', $this);
			assertType('AnonymousClassName\Foo', $this->fooProperty);
			assertType('AnonymousClassName\Foo', $this->doFoo());
		}
	};

	assertType('AnonymousClassa438d668c1555ab44fca0ccbd64d84d8', $foo);
	assertType('AnonymousClassName\Foo', $foo->fooProperty);
	assertType('AnonymousClassName\Foo', $foo->doFoo());
};
