<?php // lint > 8.0

namespace RequiredAfterOptional;

class Foo
{

	public function doFoo($foo = null, $bar): void // not OK
	{

	}

	public function doBar(int $foo = null, $bar): void // is OK
	{
	}

	public function doBaz(int $foo = 1, $bar): void // not OK
	{
	}

	public function doLorem(bool $foo = true, $bar): void // not OK
	{
	}

	public function doDolor(?int $foo = 1, $bar): void // not OK
	{
	}

	public function doSit(?int $foo = null, $bar): void // not OK
	{
	}

	public function doAmet(int|null $foo = 1, $bar): void // not OK
	{
	}

	public function doConsectetur(int|null $foo = null, $bar): void // not OK
	{
	}

	public function doAdipiscing(mixed $foo = 1, $bar): void // not OK
	{
	}

	public function doElit(mixed $foo = null, $bar): void // not OK
	{
	}

	public function doSed(int|null $foo = null, $bar, ?int $baz = null, $qux, int $quux = 1, $quuz): void // not OK
	{
	}
}
