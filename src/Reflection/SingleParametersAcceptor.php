<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

interface SingleParametersAcceptor
{

	public function flattenConditionalsInReturnType(): static;

}
