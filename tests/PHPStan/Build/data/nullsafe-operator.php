<?php // lint >= 8.1

namespace NullsafeOperator;

class Foo
{

	public ?Foo $foo = null;

	public static ?Foo $staticFoo = null;

	/** @var array<string, int> */
	public array $items = [];

	public function bar(): ?Foo
	{
		return $this->foo;
	}

	public function doFoo(?Foo $foo, ?Foo $other): void
	{
		$a = $foo !== null ? $foo->bar() : null;
		$b = null !== $foo ? $foo->foo : null;
		$c = $foo === null ? null : $foo->bar()->foo;
		$d = $this->foo !== null ? $this->foo->items['x'] : null;
		$e = self::$staticFoo !== null ? self::$staticFoo->bar() : null;
		$f = $this->foo !== null ? $this->foo->foo?->bar() : null;

		if ($foo !== null) {
			$foo->bar()->doFoo(null, null);
		}

		// it was nullable
		if ($this->foo !== null) {
			$this->foo->doFoo(
				null,
				null,
			);
		}
	}

	public function doBar(?Foo $foo, ?Foo $other): void
	{
		$a = $foo !== null ? $other->bar() : null;
		$b = $foo !== null ? $foo : null;
		$c = $foo !== null ? $foo->bar(...) : null;
		$d = $foo !== null ? $foo->bar() : 1;
		$e = $foo === null ? $foo->bar() : null;
		$f = $this->bar() !== null ? $this->bar()->foo : null;
		$g = $foo !== null ? [$foo->bar()] : null;
		$h = $foo->items !== null ? $foo->items['x'] : null;
		$i = $foo !== null ?: null;

		if ($foo !== null) {
			$foo->bar();
		} else {
			$other->bar();
		}

		if ($foo !== null) {
			$foo->bar();
			$other->bar();
		}

		if ($foo !== null) {
			$foo->foo = null;
		}

		if ($foo !== null) {
			// a comment that would be lost
			$foo->bar();
		}
	}

}
