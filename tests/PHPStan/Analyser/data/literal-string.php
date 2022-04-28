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
		assertType("''", str_repeat('', 10));
		assertType("'foofoofoofoofoofoofoofoofoofoo'", str_repeat('foo', 10));
		assertType(
			"'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'",
			str_repeat('a', 99)
		);
		assertType('literal-string&non-empty-string', str_repeat('a', 100));
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
		assertType('string', $string);
	}

}

class MoreLiteralStringFunctions
{

	/**
	 * @param literal-string $literal
	 */
	public function doFoo(string $s, string $literal)
	{
		assertType('string', addslashes($s));
		assertType('literal-string', addslashes($literal));
		assertType('string', addcslashes($s));
		assertType('literal-string', addcslashes($literal));

		assertType('string', escapeshellarg($s));
		assertType('literal-string', escapeshellarg($literal));
		assertType('string', escapeshellcmd($s));
		assertType('literal-string', escapeshellcmd($literal));

		assertType('string', strtoupper($s));
		assertType('literal-string', strtoupper($literal));
		assertType('string', strtolower($s));
		assertType('literal-string', strtolower($literal));
		assertType('string', mb_strtoupper($s));
		assertType('literal-string', mb_strtoupper($literal));
		assertType('string', mb_strtolower($s));
		assertType('literal-string', mb_strtolower($literal));
		assertType('string', lcfirst($s));
		assertType('literal-string', lcfirst($literal));
		assertType('string', ucfirst($s));
		assertType('literal-string', ucfirst($literal));
		assertType('string', ucwords($s));
		assertType('literal-string', ucwords($literal));
		assertType('string', htmlspecialchars($s));
		assertType('literal-string', htmlspecialchars($literal));
		assertType('string', htmlentities($s));
		assertType('literal-string', htmlentities($literal));

		assertType('string', urlencode($s));
		assertType('literal-string', urlencode($literal));
		assertType('string', urldecode($s));
		assertType('literal-string', urldecode($literal));
		assertType('string', rawurlencode($s));
		assertType('literal-string', rawurlencode($literal));
		assertType('string', rawurldecode($s));
		assertType('literal-string', rawurldecode($literal));

		assertType('string', preg_quote($s));
		assertType('literal-string', preg_quote($literal));

		assertType('string', sprintf($s));
		assertType('literal-string', sprintf($literal));
		assertType('string', vsprintf($s, []));
		assertType('literal-string', vsprintf($literal, []));
	}

}
