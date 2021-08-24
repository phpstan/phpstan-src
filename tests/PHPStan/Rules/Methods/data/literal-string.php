<?php

namespace LiteralStringMethod;

class Foo
{

	/**
	 * @param string $string
	 * @param literal-string $literalString
	 */
	public function doFoo(
		string $string,
		string $literalString,
		int $int
	): void
	{
		$this->requireLiteralString($string);
		$this->requireLiteralString($literalString);
		$this->requireLiteralString('foo');
		$this->requireLiteralString($int);
		$this->requireLiteralString(1);

		$mixed = doFoo();
		$this->requireLiteralString($mixed);
	}

	/**
	 * @param literal-string $s
	 */
	public function requireLiteralString(string $s): void
	{

	}

	/**
	 * @param array<literal-string> $a
	 */
	public function requireArrayOfLiteralStrings(array $a): void
	{

	}

	/**
	 * @param mixed $mixed
	 * @param array<string> $arrayOfStrings
	 * @param array<literal-string> $arrayOfLiteralStrings
	 * @param array<mixed> $arrayOfMixed
	 */
	public function doBar(
		$mixed,
		array $arrayOfStrings,
		array $arrayOfLiteralStrings,
		array $arrayOfMixed
	): void
	{
		$this->requireArrayOfLiteralStrings($mixed);
		$this->requireArrayOfLiteralStrings($arrayOfStrings);
		$this->requireArrayOfLiteralStrings($arrayOfLiteralStrings);
		$this->requireArrayOfLiteralStrings($arrayOfMixed);
	}

	public function doGet(): void
	{
		$this->requireLiteralString($_GET);
		$this->requireLiteralString($_GET['x']);
		$this->requireLiteralString($_GET['x']['y']);
	}

}
