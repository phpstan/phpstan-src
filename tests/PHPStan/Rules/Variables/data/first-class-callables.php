<?php // lint >= 8.1

namespace FirstClassCallablesDefinedVariables;

class Foo
{

	public function doFoo(): void
	{
		$foo->doFoo();
		$foo->doFoo(...);
	}

	public function doBar(object $o): void
	{
		$o->doFoo(...);
		($p = $o)->doFoo(...);
		$p->doFoo();
		$p->doFoo(...);
	}

}

class Bar
{

	public function doFoo(): void
	{
		$foo::doFoo();
		$foo::doFoo(...);
	}

	public function doBar(object $o): void
	{
		$o::doFoo(...);
		($p = $o)::doFoo(...);
		$p::doFoo();
		$p::doFoo(...);
	}

}

class Baz
{

	public function doFoo(): void
	{
		$foo();
		$foo(...);
	}

	public function doBar(object $o): void
	{
		$o(...);
		($p = $o)(...);
		$p();
		$p(...);
	}

}
