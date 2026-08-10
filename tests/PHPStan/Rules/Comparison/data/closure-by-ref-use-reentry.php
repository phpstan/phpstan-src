<?php

declare(strict_types=1);

namespace ClosureByRefUseReentry;

class Dispatcher
{

	public function dispatch(): void
	{
	}

	public static function dispatchStatic(): void
	{
	}

}

function dispatch(): void
{
}

// re-entry through a plain function call
$viaFunctionCall = false;
$a = function () use (&$viaFunctionCall): void {
	if ($viaFunctionCall) {
		return;
	}

	$viaFunctionCall = true;
	dispatch();
	$viaFunctionCall = false;
};

// re-entry through a method call
$viaMethodCall = false;
$b = function (Dispatcher $dispatcher) use (&$viaMethodCall): void {
	if ($viaMethodCall) {
		return;
	}

	$viaMethodCall = true;
	$dispatcher->dispatch();
	$viaMethodCall = false;
};

// re-entry through a static method call
$viaStaticCall = false;
$c = function () use (&$viaStaticCall): void {
	if ($viaStaticCall) {
		return;
	}

	$viaStaticCall = true;
	Dispatcher::dispatchStatic();
	$viaStaticCall = false;
};

// re-entry through a constructor
$viaNew = false;
$d = static function () use (&$viaNew): void {
	if ($viaNew) {
		return;
	}

	$viaNew = true;
	new Dispatcher();
	$viaNew = false;
};

// negated condition
$negated = false;
$e = function () use (&$negated): void {
	if (!$negated) {
		$negated = true;
		dispatch();
		$negated = false;
	}
};

// assignment and call inside a loop
$inLoop = false;
$f = function (int $times) use (&$inLoop): void {
	for ($i = 0; $i < $times; $i++) {
		if ($inLoop) {
			return;
		}

		$inLoop = true;
		dispatch();
		$inLoop = false;
	}
};

// re-entry through a call made by a nested closure
$viaNestedClosure = false;
$g = function () use (&$viaNestedClosure): void {
	if ($viaNestedClosure) {
		return;
	}

	$inner = function () use (&$viaNestedClosure): void {
		$viaNestedClosure = true;
		dispatch();
		$viaNestedClosure = false;
	};
	$inner();
};

// re-entry while the generator is suspended on yield
$viaYield = false;
$h = function () use (&$viaYield): \Generator {
	if ($viaYield) {
		return;
	}

	$viaYield = true;
	yield 1;
	$viaYield = false;
};

// re-entry while the generator is suspended on yield from
$viaYieldFrom = false;
$i = function () use (&$viaYieldFrom): \Generator {
	if ($viaYieldFrom) {
		return;
	}

	$viaYieldFrom = true;
	yield from [1, 2];
	$viaYieldFrom = false;
};
