<?php

namespace ClosureBindThisFromNewThisProperties;

class Foo
{

	private int $secret = 1;

}

class NoParent
{

}

class Test
{

	public function doFoo(object $object, NoParent $noParent): void
	{
		// the hydrator idiom: an `object` bound into the class whose private property it reads
		$secret = \Closure::bind(function () {
			return $this->secret;
		}, $object, Foo::class);
		$secret = \Closure::bind(fn () => $this->secret, $object, Foo::class);

		// $this is the bound object, which has no $secret
		$secret = \Closure::bind(function () {
			return $this->secret;
		}, $noParent, Foo::class);
	}

}
