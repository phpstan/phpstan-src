<?php // lint >= 8.1

namespace Bug6192;

class Foo {
	public function __construct(
		public string $value = 'default foo foo'
	) {}
}

class Bar {
	public function __construct(
		public Foo $foo = new Foo('default bar foo')
	) {}
}

class Baz
{

	public function doFoo(): void
	{
		echo "Testing Foo\n";
		var_export(new Foo('testing foo'));
		echo "\n";
	}

	public function doBar(): void
	{
		echo "Testing Bar\n";
		var_export(new Bar(new Foo('testing bar')));
		echo "\n";
	}

}
