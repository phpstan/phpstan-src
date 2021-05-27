<?php declare(strict_types = 1);

namespace App\MethodCall;

use PHPStan\Analyser\ScopeContext;
use PHPStan\Command\CommandHelper;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;

class Foo
{

	public function doFoo(): void
	{
		CommandHelper::begin();
	}

	public function doBar(FunctionReflection $f): void
	{
		ParametersAcceptorSelector::selectSingle($f->getVariants()); // @api above class
		ScopeContext::create(__DIR__ . '/test.php'); // @api above method
	}

}
