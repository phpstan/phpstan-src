<?php

namespace NonEmptyString;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(string $s): void
	{
		if ($s === '') {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doBar(string $s): void
	{
		if ($s !== '') {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doFoo2(string $s): void
	{
		if (strlen($s) === 0) {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doBar2(string $s): void
	{
		if (strlen($s) > 0) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doBar3(string $s): void
	{
		if (strlen($s) >= 1) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doFoo5(string $s): void
	{
		if (0 === strlen($s)) {
			return;
		}

		assertType('non-empty-string', $s);
	}

	public function doBar4(string $s): void
	{
		if (0 < strlen($s)) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doBar5(string $s): void
	{
		if (1 <= strlen($s)) {
			assertType('non-empty-string', $s);
			return;
		}

		assertType('\'\'', $s);
	}

	public function doFoo3(string $s): void
	{
		if ($s) {
			assertType('non-empty-string', $s);
		} else {
			assertType('\'\'|\'0\'', $s);
		}
	}

	/**
	 * @param non-empty-string $s
	 */
	public function doFoo4(string $s): void
	{
		assertType('array<int, string>&nonEmpty', explode($s, 'foo'));
	}

	/**
	 * @param non-empty-string $s
	 */
	public function doWithNumeric(string $s): void
	{
		if (!is_numeric($s)) {
			return;
		}

		assertType('string&numeric', $s);
	}

}
