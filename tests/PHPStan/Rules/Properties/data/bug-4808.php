<?php

namespace Bug4808;

class A
{
	/** @var bool */
	private $x = true;

	public function preventUnusedVariable(): bool
	{
		return $this->x;
	}
}

class B extends A
{
	public function getPrivateProp(): bool
	{
		return \Closure::bind(function () {
			return $this->x;
		}, $this, A::class)();
	}
}
