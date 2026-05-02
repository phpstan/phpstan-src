<?php

namespace DynamicStaticCall;

class Foo {
	/** @phpstan-pure */
	static public function doFoo():int
	{
		return 42;
	}
}

final class FinalFoo {
	/** @phpstan-pure */
	static public function doFoo():int
	{
		return 42;
	}
}

class Bar {
	/** @phpstan-pure */
	final static public function finalFoo():int
	{
		return 42;
	}
}


class Baz {
	function doBaz(Foo $foo, FinalFoo $finalFoo, Bar $bar):void {
		$foo::doFoo();
		$finalFoo::doFoo();
		$bar::finalFoo();
	}
}
