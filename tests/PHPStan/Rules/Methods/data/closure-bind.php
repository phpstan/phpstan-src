<?php

namespace CallClosureBind;

class Bar
{

	public function fooMethod(): Foo
	{
		\Closure::bind(function (Foo $foo) {
			$foo->privateMethod();
			$foo->nonexistentMethod();
		}, null, Foo::class);

		$this->fooMethod();
		$this->barMethod();
		$foo = new Foo();
		$foo->privateMethod();
		$foo->nonexistentMethod();

		\Closure::bind(function () {
			$this->fooMethod();
			$this->barMethod();
		}, $nonexistent, self::class);

		\Closure::bind(function (Foo $foo) {
			$foo->privateMethod();
			$foo->nonexistentMethod();
		}, null, 'CallClosureBind\Foo');

		\Closure::bind(function (Foo $foo) {
			$foo->privateMethod();
			$foo->nonexistentMethod();
		}, null, new Foo());

		\Closure::bind(function (Foo $foo) {
			$foo->privateMethod();
			$foo->nonexistentMethod();
		}, null, get_class(new Foo()));

		\Closure::bind(function () {
			// $this is Foo
			$this->privateMethod();
			$this->nonexistentMethod();
		}, $this->fooMethod(), Foo::class);

		(function () {
			$this->publicMethod();
		})->call(new Foo());
	}

	public function x(): bool
	{
		return 1.0;
	}

	public function testClassString(): bool
	{
		$fx = function () {
			return $this->x();
		};

		$res = 0.0;
		$res += \Closure::bind($fx, $this)();
		$res += \Closure::bind($fx, $this, 'static')();
		$res += \Closure::bind($fx, $this, Foo2::class)();
		$res += \Closure::bind($fx, $this, 'CallClosureBind\Bar2')();
		$res += \Closure::bind($fx, $this, 'CallClosureBind\Bar3')();

		$res += $fx->bindTo($this)();
		$res += $fx->bindTo($this, 'static')();
		$res += $fx->bindTo($this, Foo2::class)();
		$res += $fx->bindTo($this, 'CallClosureBind\Bar2')();
		$res += $fx->bindTo($this, 'CallClosureBind\Bar3')();

		return $res;
	}

}

class Bar2 extends Bar {}
