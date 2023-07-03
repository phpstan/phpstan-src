<?php // lint >= 8.0

namespace Bug6039;

trait Foo
{
	public function showFoo(): void
	{
		echo 'foo' . self::postFoo();
	}

	abstract private static function postFoo(): string;
}

class UseFoo
{
	use Foo {
		showFoo as showFooTrait;
	}

	public function showFoo(): void
	{
		echo 'fooz';
		$this->showFooTrait();
	}

	private static function postFoo(): string
	{
		return 'postFoo';
	}
}
