<?php

namespace PregMatchTypeSpecifying;

use function PHPStan\Testing\assertType;

class Foo {
	public function unknownFlags(int $flags)
	{
		// we don't know the flags, therefore allow all possible variations.
		if (preg_match('/^[a-z]+$/', 'foo', $matches, $flags)) {
			assertType('array<int<0, max>|string, array{string|null, int<-1, max>}>', $matches);
		} else {
			assertType('array', $matches);
		}
	}

	public function matchVariants(string $str) {
		if (preg_match('/^[a-z]+$/', 'foo', $matches)) {
			assertType('array<int<0, max>|string, string>', $matches);
		} else {
			assertType('array', $matches);
		}
		if (preg_match($str, $str, $matches)) {
			assertType('array<int<0, max>|string, string>', $matches);
		} else {
			assertType('array', $matches);
		}
		if (preg_match('/^[a-z]+$/', 'foo', $matches, 0)) {
			assertType('array<int<0, max>|string, string>', $matches);
		} else {
			assertType('array', $matches);
		}

		if (preg_match('/(?P<name>\w+): (?P<digit>\d+)/', $str, $matches)) {
			assertType('array<int<0, max>|string, string>', $matches);
		} else {
			assertType('array', $matches);
		}

		if (preg_match('/(a)(b)*(c)/', 'ac', $matches, PREG_UNMATCHED_AS_NULL)) {
			assertType('array<int<0, max>|string, string|null>', $matches);
		} else {
			assertType('array', $matches);
		}

		if (preg_match('/(foo)(bar)(baz)/', 'foobarbaz', $matches, PREG_OFFSET_CAPTURE)) {
			assertType('array<int<0, max>|string, array{string, int<-1, max>}>', $matches);
		} else {
			assertType('array', $matches);
		}

		if (preg_match('/(foo)(bar)(baz)/', 'foobarbaz', $matches, PREG_OFFSET_CAPTURE, 2)) {
			assertType('array<int<0, max>|string, array{string, int<-1, max>}>', $matches);
		} else {
			assertType('array', $matches);
		}

		// see https://3v4l.org/TuUp4
		if (preg_match('/(a)(b)*(c)/', 'ac', $matches, PREG_UNMATCHED_AS_NULL|PREG_OFFSET_CAPTURE)) {
			assertType('array<int<0, max>|string, array{string|null, int<-1, max>}>', $matches);
		} else {
			assertType('array', $matches);
		}

		// just testing, our ext won't error on calls with just 2 args
		if (preg_match("/\bweb\b/i", "PHP is the web scripting language of choice.")) {
			echo "A match was found.";
		} else {
			echo "A match was not found.";
		}
	}
}
