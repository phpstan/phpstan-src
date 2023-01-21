<?php declare(strict_types = 1);

namespace TraitInstanceOf;

use function PHPStan\Testing\assertType;

trait Foo {
	public function test(): string {
		assertType('$this(TraitInstanceOf\HelloWorld)', $this);
		if ($this instanceof HelloWorld) {
			$this->bar();

			assertType('$this(TraitInstanceOf\HelloWorld)', $this);
			return 'hello world';
		}
		assertType('$this(TraitInstanceOf\HelloWorld~$this(TraitInstanceOf\HelloWorld))', $this);
		if ($this instanceof OtherClass) {
			$this->doOther();

			assertType('$this(TraitInstanceOf\HelloWorld~$this(TraitInstanceOf\HelloWorld))&$this(TraitInstanceOf\OtherClass~$this(TraitInstanceOf\HelloWorld))', $this);
			return 'other class';
		}
		assertType('$this(TraitInstanceOf\HelloWorld~$this(TraitInstanceOf\HelloWorld))', $this);

		return 'no';
	}
}

class HelloWorld
{
	use Foo;

	function bar(): string {
		return $this->test();
	}
}

class OtherClass {
	public function doOther():void {
	}
}
