<?php

namespace LiteralString;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param literal-string $literalString
	 * @param numeric-string $numericString
	 */
	public function doFoo($literalString, string $string, $numericString)
	{
		assertType('literal-string', $literalString);
		assertType('literal-string', $literalString . '');
		assertType('literal-string', '' . $literalString);
		assertType('literal-string&non-empty-string', $literalString . '0');
		assertType('literal-string&non-empty-string', '0' . $literalString);
		assertType('literal-string&non-falsy-string', $literalString . 'foo');
		assertType('literal-string&non-falsy-string', 'foo' . $literalString);
		assertType('literal-string&non-falsy-string', "foo ${literalString}");
		assertType('literal-string&non-falsy-string', "${literalString} foo");
		assertType('string', $string . '');
		assertType('string', '' . $string);
		assertType('string', $literalString . $string);
		assertType('string', $string . $literalString);
		assertType('literal-string', $literalString . $literalString);

		assertType('string', str_repeat($string, 10));
		assertType('literal-string', str_repeat($literalString, 10));
		assertType("''", str_repeat('', 10));
		assertType("'foofoofoofoofoofoofoofoofoofoo'", str_repeat('foo', 10));
		assertType(
			"'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'",
			str_repeat('a', 99)
		);
		assertType('literal-string&non-falsy-string', str_repeat('a', 100));
		assertType('literal-string&non-empty-string&numeric-string', str_repeat('0', 100));
		assertType('literal-string&non-falsy-string&numeric-string', str_repeat('1', 100));
		// Repeating a numeric type multiple times can lead to a non-numeric type: 3v4l.org/aRBdZ
		assertType('non-empty-string', str_repeat($numericString, 100));
		assertType('literal-string&non-falsy-string', str_repeat('1.23', 100));

		assertType("'?,?,?,'", str_repeat('?,', 3));
		assertType("*NEVER*", str_repeat('?,', -3));

		assertType('non-empty-string', str_pad($string, 5, $string));
		assertType('non-empty-string', str_pad($literalString, 5, $string));
		assertType('non-empty-string', str_pad($string, 5, $literalString));
		assertType('literal-string&non-empty-string', str_pad($literalString, 5, $literalString));
		assertType('literal-string&non-empty-string', str_pad($literalString, 5));

		assertType('string', implode([$string]));
		assertType('literal-string', implode([$literalString]));
		assertType('string', implode($string, [$literalString]));
		assertType('literal-string', implode($literalString, [$literalString]));
		assertType('string', implode($literalString, [$string]));
	}

	/** @param literal-string $literalString */
	public function increment($literalString, string $string)
	{
		$literalString++;
		assertType('literal-string', $literalString);

		$string++;
		assertType('(float|int|string)', $string);
	}

}
