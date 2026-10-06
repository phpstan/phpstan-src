<?php

namespace ClosureBindThisFromNewThis;

class Foo
{

	private function im(): void
	{
	}

}

class NoParent
{

}

class Test
{

	public function doFoo(object $object, NoParent $noParent): void
	{
		// $this is the bound object, which has no im()
		\Closure::bind(function () {
			$this->im();
		}, $noParent, Foo::class);
		\Closure::bind(fn () => $this->im(), new NoParent(), Foo::class);

		// the hydrator idiom: an `object` bound into the class whose private members it calls
		\Closure::bind(function () {
			$this->im();
		}, $object, Foo::class);
	}

}
