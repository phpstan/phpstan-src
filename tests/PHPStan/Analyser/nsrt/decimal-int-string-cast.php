<?php declare(strict_types = 1);

namespace DecimalIntStringCast;

use function PHPStan\Testing\assertType;
use function intval;
use function strval;

class Foo
{

	public function castIdentical(string $s): void
	{
		if ((string) (int) $s === $s) {
			assertType('decimal-int-string', $s);
		} else {
			assertType('non-decimal-int-string', $s);
		}
	}

	public function castIdenticalFlipped(string $s): void
	{
		if ($s === (string) (int) $s) {
			assertType('decimal-int-string', $s);
		} else {
			assertType('non-decimal-int-string', $s);
		}
	}

	public function castNotIdentical(string $s): void
	{
		if ((string) (int) $s !== $s) {
			assertType('non-decimal-int-string', $s);
		} else {
			assertType('decimal-int-string', $s);
		}
	}

	public function strvalIntval(string $s): void
	{
		if (strval(intval($s)) === $s) {
			assertType('decimal-int-string', $s);
		} else {
			assertType('non-decimal-int-string', $s);
		}
	}

	public function mixedCastForms(string $s): void
	{
		if (strval((int) $s) === $s) {
			assertType('decimal-int-string', $s);
		}

		if ((string) intval($s) === $s) {
			assertType('decimal-int-string', $s);
		}
	}

	public function notAlwaysString(int|string $s): void
	{
		if ((string) (int) $s === $s) {
			assertType('decimal-int-string', $s);
		} else {
			// $s can still be an int here, so we cannot narrow to non-decimal-int-string
			assertType('int|string', $s);
		}
	}

}
