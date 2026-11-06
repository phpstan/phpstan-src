<?php

namespace UnthrownExceptionPhpVersionRange;

class MyException extends \Exception
{

}

class ThrowsOnToString
{

	/** @throws MyException */
	public function __toString(): string
	{
		throw new MyException();
	}

}

final class Bar
{

	/** @var int */
	public $a;

}

class Foo
{

	public function dynamicFetch(Bar $o, string $s): void
	{
		try {
			$a = $o->$s;
		} catch (MyException $e) { // implicit throw point - a property hook might run on PHP 8.4+

		}
	}

	public function dynamicAssign(Bar $o, string $s): void
	{
		try {
			$o->$s = 1;
		} catch (MyException $e) { // implicit throw point - a property hook might run on PHP 8.4+

		}
	}

	public function stringCast(ThrowsOnToString $o): string
	{
		try {
			return (string) $o;
		} catch (MyException $e) { // __toString() may throw on PHP 7.4+
			return '';
		}
	}

}
