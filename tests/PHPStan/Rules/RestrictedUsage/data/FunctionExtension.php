<?php

namespace RestrictedUsage;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;

class FunctionExtension implements RestrictedFunctionUsageExtension
{

	public function isRestrictedFunctionUsage(
		FunctionReflection $functionReflection,
		Scope $scope
	): ?RestrictedUsage
	{
		if ($functionReflection->getName() !== 'RestrictedUsage\\doFoo') {
			return null;
		}

		return RestrictedUsage::create('Cannot call doFoo', 'restrictedUsage.doFoo', 'Use doBar() instead.');
	}

}
