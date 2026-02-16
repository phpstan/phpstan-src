<?php

namespace Bug12272;

interface ExceptionContract
{
	public function __construct(string $message);
}

class BaseException extends Exception implements ExceptionContract
{
	public function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct($message, 0, $previous);
	}
}

class SomeException extends BaseException
{
}

class SpecificException extends SomeException
{
	public function __construct(string $message, int $code = 0)
	{
		if ($code) {
			echo 1;
		}

		parent::__construct($message);
	}
}
