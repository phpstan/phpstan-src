<?php declare(strict_types = 1);

namespace Bug14774;

use function PHPStan\Testing\assertType;

class CoffeeBreak
{

	public int $blah;

	public function returnInt(): int
	{
		return 1;
	}

	public function blah(Something $s): void
	{
		if (isset($this->blah) && $s !== Something::Another) {
			throw new \Exception();
		}

		if ($s !== Something::Another) {
			$x = $this->returnInt();
			assertType('int', $x);
			assertType('int', $this->blah);
		}
	}

	public function withBoolAnd(bool $cond): void
	{
		if (isset($this->blah) && $cond) {
			throw new \Exception();
		}

		if ($cond) {
			$x = $this->returnInt();
			assertType('int', $x);
			assertType('int', $this->blah);
		}
	}

	public function withBoolOr(bool $cond): void
	{
		if (!isset($this->blah) || $cond) {
			return;
		}

		assertType('false', $cond);
		assertType('int', $this->blah);
	}

	public function withEmpty(bool $cond): void
	{
		if (!empty($this->blah) && $cond) {
			throw new \Exception();
		}

		if ($cond) {
			$x = $this->returnInt();
			assertType('int', $x);
		}
	}

}

class StaticProp
{

	public static int $blah;

	public function returnInt(): int
	{
		return 1;
	}

	public function m(bool $cond): void
	{
		if (isset(self::$blah) && $cond) {
			throw new \Exception();
		}

		if ($cond) {
			$x = $this->returnInt();
			assertType('int', $x);
			assertType('int', self::$blah);
		}
	}

}

enum Something: string
{

	case Some = 'some';
	case Thing = 'thing';
	case Another = 'another';

}
