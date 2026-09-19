<?php declare(strict_types = 1);

namespace Bug15271;

use ArgumentCountError;
use ArithmeticError;
use AssertionError;
use DivisionByZeroError;
use Error;
use Exception;
use TypeError;
use UnhandledMatchError;
use ValueError;

class MondayMorning
{

	public function test(): void
	{
		str_decrement('B'); // Should not throw for alphanumeric ASCII string in decrement range.
		str_increment('B'); // Should not throw for alphanumeric ASCII string
		get_class($this); // Should not throw when an object is passed
		get_called_class(); // Should not throw inside a class
	}

	public function throwsError(): void
	{
		throw new Error();
	}

	public function throwsTypeError(): void
	{
		throw new TypeError();
	}

	public function throwsValueError(): void
	{
		throw new ValueError();
	}

	public function throwsArithmeticError(): void
	{
		throw new ArithmeticError();
	}

	public function throwsDivisionByZeroError(): void
	{
		throw new DivisionByZeroError();
	}

	public function throwsArgumentCountError(): void
	{
		throw new ArgumentCountError();
	}

	public function throwsAssertionError(): void
	{
		throw new AssertionError();
	}

	public function throwsUnhandledMatchError(): void
	{
		throw new UnhandledMatchError();
	}

	public function throwsException(): void
	{
		throw new Exception();
	}

}

function callsNativeFunctions(): void
{
	get_class(new MondayMorning());
	get_called_class();
}

function throwsErrorFunction(): void
{
	throw new Error();
}

function throwsExceptionFunction(): void
{
	throw new Exception();
}
