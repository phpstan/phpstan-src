<?php declare(strict_types = 1);

namespace PHPStan;

final class ShouldNotHappenException extends \Exception
{

	/** @api */
	public function __construct(string $message = 'Internal error.')
	{
		parent::__construct($message);
	}

}
