<?php declare(strict_types = 1);

namespace Bug4510;

class HelloWorld
{
	public function doSomething(string $method): void {
		if (!method_exists($this, $method)) {
			return;
		}

		[$this, $method](); // error - method_exists doesn't imply callable
	}
}

function bar(string $method): void {
	$instance = new HelloWorld();
	if (!method_exists($instance, $method)) {
		return;
	}

	[$instance, $method](); // error - method_exists doesn't imply callable
}

function baz(string $method): void {
	$instance = new HelloWorld();
	if (!is_callable([$instance, $method])) {
		return;
	}

	[$instance, $method](); // ok - is_callable verifies callability
}

function withClassString(string $method): void {
	if (!method_exists(HelloWorld::class, $method)) {
		return;
	}

	[HelloWorld::class, $method](); // error - method_exists doesn't imply callable
}

function withDynamicMethodExistsAndVariable(string $method): void {
	$instance = new HelloWorld();
	$callable = [$instance, $method];
	if (!is_callable($callable)) {
		return;
	}

	$callable(); // ok - is_callable on variable already worked
}

function methodExistsInElseBranch(string $method): void {
	$instance = new HelloWorld();
	if (method_exists($instance, $method)) {
		[$instance, $method](); // error - method_exists doesn't imply callable
	}
}

function isCallableInElseBranch(string $method): void {
	$instance = new HelloWorld();
	if (is_callable([$instance, $method])) {
		[$instance, $method](); // ok - is_callable verifies callability
	}
}

function isCallableWithClassString(string $method): void {
	if (!is_callable([HelloWorld::class, $method])) {
		return;
	}

	[HelloWorld::class, $method](); // ok - is_callable verifies callability
}

function isCallableWithThis(string $method): void {
	$instance = new HelloWorld();
	if (!is_callable([$instance, $method])) {
		return;
	}

	[$instance, $method](); // ok - is_callable verifies callability
}

function isCallableWithExplicitKeys(string $method): void {
	$instance = new HelloWorld();
	if (!is_callable([0 => $instance, 1 => $method])) {
		return;
	}

	[0 => $instance, 1 => $method](); // ok - is_callable verifies callability
}
