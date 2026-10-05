<?php

namespace ClosureBindThisFromNewThisVariables;

class Foo
{

	public function doFoo(): void
	{
	}

}

function (Foo $foo): void {
	// a null $newThis binds no object, whatever the scope
	\Closure::bind(function () {
		$this->doFoo();
	}, null, Foo::class);
	\Closure::bind(fn () => $this->doFoo(), null, Foo::class);

	\Closure::bind(function () {
		$this->doFoo();
	}, $foo, Foo::class);
};
