<?php

namespace Bug4886;

trait TOne
{
	public function sayHello(): void
	{
		echo 'One';
	}
}

trait TTwo
{
	public function sayBello(): void
	{
		echo 'Two';
	}
}

class Foo
{
	use TOne, TTwo {
		sayBello as niceAlias;
	}

	public function boo(): void
	{
		static::niceAlias();
	}
}
