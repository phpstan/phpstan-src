<?php

namespace LiteralString;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @param literal-string $literalString */
	public function doFoo($literalString, string $string)
	{
		assertType('literal-string', $literalString);
		assertType('literal-string', $literalString . '');
		assertType('literal-string', '' . $literalString);
		assertType('literal-string&non-empty-string', $literalString . 'foo');
		assertType('literal-string&non-empty-string', 'foo' . $literalString);
		assertType('literal-string&non-empty-string', "foo ${literalString}");
		assertType('literal-string&non-empty-string', "${literalString} foo");
		assertType('string', $string . '');
		assertType('string', '' . $string);
		assertType('string', $literalString . $string);
		assertType('string', $string . $literalString);
		assertType('literal-string', $literalString . $literalString);

		assertType('string', str_repeat($string, 10));
		assertType('literal-string', str_repeat($literalString, 10));
		assertType('literal-string', str_repeat('', 10));
		assertType('literal-string&non-empty-string', str_repeat('foo', 10));

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
		assertType('string', $string);
	}

	/**
	 * @param string $s
	 * @param literal-string $ls
	 * @param numeric-string $ns
	 * @param int $i
	 * @param float $f
	 * @param bool $b
	 */
	public function scalarConcat($s, $ls, $ns, $i, $f, $b): void
	{
		$literal = "hello";

		assertType("non-empty-string", $literal . $s);
		assertType("literal-string&non-empty-string", $literal . $ls);
		assertType("literal-string&non-empty-string", $literal . $ns);

		assertType("literal-string&non-empty-string", $literal . $i);
		assertType("literal-string&non-empty-string", $literal . $f);
		assertType("literal-string&non-empty-string", $literal . $b);
	}
}
