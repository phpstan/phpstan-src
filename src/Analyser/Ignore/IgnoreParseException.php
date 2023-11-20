<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use Exception;

class IgnoreParseException extends Exception
{

	public function __construct(string $message, private int $phpDocLine)
	{
		parent::__construct($message);
	}

	public function getPhpDocLine(): int
	{
		return $this->phpDocLine;
	}

}
