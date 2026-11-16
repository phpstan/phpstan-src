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

	public function rangeWithInvalidStep(): void
	{
		try {
			range(1, 10, 0);
		} catch (\ValueError $e) { // PHP 8 throws, PHP 7 returns false

		}
	}

	public function rangeWithUnknownBoundary(int $i): void
	{
		try {
			range(1, $i);
		} catch (\ValueError $e) { // a range too long for an array throws on PHP 8

		}
	}

}
