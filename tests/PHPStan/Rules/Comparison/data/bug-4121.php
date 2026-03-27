<?php declare(strict_types = 1);

namespace Bug4121;

trait Foo
{
	public function bar(): void
	{
		echo self::class === One::class
			? "-ONE-"
			: "-TWO-";
	}
}

class One
{
	use Foo;
}

class Two
{
	use Foo;
}
