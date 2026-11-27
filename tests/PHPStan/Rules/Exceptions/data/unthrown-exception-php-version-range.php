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

	public function arrayChunk(int $length): void
	{
		try {
			array_chunk([1], $length);
		} catch (\ValueError $e) { // a length below 1 throws on PHP 8

		}
	}

	public function strSplit(string $s, int $length): void
	{
		try {
			str_split($s, $length);
		} catch (\ValueError $e) { // a length below 1 throws on PHP 8

		}
	}

	public function minOfArray(array $a): void
	{
		try {
			min($a);
		} catch (\ValueError $e) { // an empty array throws on PHP 8

		}
	}

	public function maxOfArray(array $a): void
	{
		try {
			max($a);
		} catch (\ValueError $e) { // an empty array throws on PHP 8

		}
	}

	public function sprintfWithUnknownFormat(string $format): void
	{
		try {
			sprintf($format, 1);
		} catch (\ValueError $e) { // an invalid format throws on PHP 8

		}
	}

	public function vprintfWithUnknownFormat(string $format, array $values): void
	{
		try {
			vprintf($format, $values);
		} catch (\ValueError $e) { // an invalid format throws on PHP 8

		}
	}

	public function versionCompareWithUnknownOperator(string $operator): void
	{
		try {
			version_compare('1.0', '2.0', $operator);
		} catch (\ValueError $e) { // an invalid operator throws on PHP 8

		}
	}

	public function arrayCombine(array $keys, array $values): void
	{
		try {
			array_combine($keys, $values);
		} catch (\ValueError $e) { // arrays of different sizes throw on PHP 8

		}
	}

	public function roundWithUnknownMode(float $f, int $mode): void
	{
		try {
			round($f, 0, $mode);
		} catch (\ValueError $e) { // an invalid mode throws on PHP 8.4+

		}
	}

}
