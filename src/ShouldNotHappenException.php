<?php declare(strict_types = 1);

namespace PHPStan;

use LogicException;

final class ShouldNotHappenException extends LogicException
{

	/** @api */
	public function __construct(string $message = 'Internal error.')
	{
		parent::__construct($message);
	}

}
