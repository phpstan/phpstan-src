<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

interface SingleParametersAcceptor
{

	/**
	 * @return static
	 */
	public function flattenConditionalsInReturnType(): self;

}
