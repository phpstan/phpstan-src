<?php

namespace PregSplit;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function doFoo()
	{
		assertType('*ERROR*', preg_split('/[0-9a]', '1-2-3'));
		assertType("array{''}", preg_split('/-/', ''));
		assertType("array{}", preg_split('/-/', '', -1, PREG_SPLIT_NO_EMPTY));
		assertType("array{'1', '-', '2', '-', '3'}", preg_split('/ *(-) */', '1- 2-3', -1, PREG_SPLIT_DELIM_CAPTURE));
		assertType("array{array{'', 0}}", preg_split('/-/', '', -1, PREG_SPLIT_OFFSET_CAPTURE));
		assertType("array{}", preg_split('/-/', '', -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE));
		assertType("array{'1', '2', '3'}", preg_split('/-/', '1-2-3'));
		assertType("array{'1', '2', '3'}", preg_split('/-/', '1-2-3', -1, PREG_SPLIT_NO_EMPTY));
		assertType("array{'1', '3'}", preg_split('/-/', '1--3', -1, PREG_SPLIT_NO_EMPTY));
		assertType("array{array{'1', 0}, array{'2', 2}, array{'3', 4}}", preg_split('/-/', '1-2-3', -1, PREG_SPLIT_OFFSET_CAPTURE));
		assertType("array{array{'1', 0}, array{'2', 2}, array{'3', 4}}", preg_split('/-/', '1-2-3', -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE));
		assertType("array{array{'1', 0}, array{'', 2}, array{'3', 3}}", preg_split('/-/', '1--3', -1, PREG_SPLIT_OFFSET_CAPTURE));
		assertType("array{array{'1', 0}, array{'3', 3}}", preg_split('/-/', '1--3', -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE));
	}

	public function doWithVariables(string $pattern, string $subject, int $offset, int $flags): void
	{
		assertType('list<array{string, int<0, max>}|string>|false', preg_split($pattern, $subject, $offset, $flags));
		assertType('list<array{string, int<0, max>}|string>|false', preg_split("//", $subject, $offset, $flags));

		assertType('non-empty-list<array{string, int<0, max>}|string>|false', preg_split($pattern, "1-2-3", $offset, $flags));
		assertType('list<array{string, int<0, max>}|string>|false', preg_split($pattern, $subject, -1, $flags));
		assertType('list<non-empty-string>|false', preg_split($pattern, $subject, $offset, PREG_SPLIT_NO_EMPTY));
		assertType('list<array{string, int<0, max>}>|false', preg_split($pattern, $subject, $offset, PREG_SPLIT_OFFSET_CAPTURE));
		assertType("list<string>|false", preg_split($pattern, $subject, $offset, PREG_SPLIT_DELIM_CAPTURE));
		assertType('list<array{string, int<0, max>}>|false', preg_split($pattern, $subject, $offset, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE));
	}

	/**
	 * @param non-empty-string $nonEmptySubject
	 */
	public function doWithNonEmptySubject(string $pattern, string $nonEmptySubject, int $offset, int $flags): void
	{
		assertType('non-empty-list<string>|false', preg_split("//", $nonEmptySubject));

		assertType('non-empty-list<array{string, int<0, max>}|string>|false', preg_split($pattern, $nonEmptySubject, $offset, $flags));
		assertType('non-empty-list<array{string, int<0, max>}|string>|false', preg_split("//", $nonEmptySubject, $offset, $flags));

		assertType('non-empty-list<array{string, int<0, max>}>|false', preg_split("/-/", $nonEmptySubject, $offset, PREG_SPLIT_OFFSET_CAPTURE));
		assertType('non-empty-list<non-empty-string>|false', preg_split("/-/", $nonEmptySubject, $offset, PREG_SPLIT_NO_EMPTY));
		assertType('non-empty-list<string>|false', preg_split("/-/", $nonEmptySubject, $offset, PREG_SPLIT_DELIM_CAPTURE));
		assertType('non-empty-list<array{string, int<0, max>}>|false', preg_split("/-/", $nonEmptySubject, $offset, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE));
		assertType('non-empty-list<array{non-empty-string, int<0, max>}>|false', preg_split("/-/", $nonEmptySubject, $offset, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE));
		assertType('non-empty-list<non-empty-string>|false', preg_split("/-/", $nonEmptySubject, $offset, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
	}

	/**
	 * @param string $pattern
	 * @param string $subject
	 * @param int $limit
	 * @param int $flags PREG_SPLIT_NO_EMPTY or PREG_SPLIT_DELIM_CAPTURE
	 * @return list<array{string, int}>
	 * @phpstan-return list<array{string, int<0, max>}>
	 */
	public static function splitWithOffset($pattern, $subject, $limit = -1, $flags = 0)
	{
		assertType('list<array{string, int<0, max>}>|false', preg_split($pattern, $subject, $limit, $flags | PREG_SPLIT_OFFSET_CAPTURE));
		assertType('list<array{string, int<0, max>}>|false', preg_split($pattern, $subject, $limit, PREG_SPLIT_OFFSET_CAPTURE | $flags));
		assertType('list<array{non-empty-string, int<0, max>}>|false', preg_split($pattern, $subject, $limit, PREG_SPLIT_OFFSET_CAPTURE | $flags | PREG_SPLIT_NO_EMPTY));
	}

	/**
	 * @param string $pattern
	 * @param string $subject
	 * @param int $limit
	 */
	public static function dynamicFlags($pattern, $subject, $limit = -1)
	{
		$flags = PREG_SPLIT_OFFSET_CAPTURE;

		if ($subject === '1-2-3') {
			$flags |= PREG_SPLIT_NO_EMPTY;
		}

		assertType('list<array{string, int<0, max>}>|false', preg_split($pattern, $subject, $limit, $flags));
	}
}
