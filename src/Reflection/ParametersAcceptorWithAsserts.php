<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/** @api */
interface ParametersAcceptorWithAsserts extends ParametersAcceptor
{

	public function getAsserts(): Assertions;

}
