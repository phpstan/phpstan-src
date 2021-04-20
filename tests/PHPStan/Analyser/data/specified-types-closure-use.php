<?php

namespace SpecifiedTypesClosureUse;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(MethodCall $call, MethodCall $bar): void
	{
		if ($call->name instanceof Identifier && $bar->name instanceof Identifier) {
			function () use ($call): void {
				assertType('PhpParser\Node\Identifier', $call->name);
				assertType('mixed', $bar->name);
			};

			assertType('PhpParser\Node\Identifier', $call->name);
		}
	}

	public function doBar(MethodCall $call, MethodCall $bar): void
	{
		if ($call->name instanceof Identifier && $bar->name instanceof Identifier) {
			$a = 1;
			function () use ($call, &$a): void {
				assertType('PhpParser\Node\Identifier', $call->name);
				assertType('mixed', $bar->name);
			};

			assertType('PhpParser\Node\Identifier', $call->name);
		}
	}

}
