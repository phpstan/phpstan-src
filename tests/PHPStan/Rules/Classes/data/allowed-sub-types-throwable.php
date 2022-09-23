<?php

namespace AllowedSubTypesThrowable;

use Exception;
use Throwable;

class MyException extends Exception {}

class MyError implements Throwable
{

	public function getMessage(): string
	{
	}

	public function getCode(): int
	{
	}

	public function getFile(): string
	{
	}

	public function getLine(): int
	{
	}

	public function getTrace(): array
	{
	}

	public function getTraceAsString(): string
	{
	}

	public function getPrevious(): ?Throwable
	{
	}

	public function __toString(): string
	{
	}

}
