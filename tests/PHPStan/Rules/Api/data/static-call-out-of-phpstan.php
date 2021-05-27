<?php declare(strict_types = 1);

namespace App\StaticCall;

use PHPStan\Analyser\ScopeContext;
use PHPStan\Command\CommandHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ObjectType;

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

class Bar extends InClassNode
{

	public function __construct()
	{
		parent::__construct();
	}

}

class Baz extends ObjectType
{

	public function __construct()
	{
		parent::__construct();
	}

}
