<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use Exception;

/**
 * Thrown by a method only the active turbo extension implements.
 *
 * @api
 */
final class TurboNotActiveException extends Exception
{

	public function __construct()
	{
		parent::__construct('The turbo extension is not active.');
	}

}
