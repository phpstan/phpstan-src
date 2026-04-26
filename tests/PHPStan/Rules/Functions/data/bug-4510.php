<?php declare(strict_types = 1);

namespace Bug4510;

class HelloWorld
{
	public function doSomething(string $method): void {
		if (!method_exists($this, $method)) {
			return;
		}

		[$this, $method]();
	}
}

function bar(string $method): void {
	$instance = new HelloWorld();
	if (!method_exists($instance, $method)) {
		return;
	}

	[$instance, $method]();
}

function baz(string $method): void {
	$instance = new HelloWorld();
	if (!is_callable([$instance, $method])) {
		return;
	}

	[$instance, $method]();
}

function withClassString(string $method): void {
	if (!method_exists(HelloWorld::class, $method)) {
		return;
	}

	[HelloWorld::class, $method]();
}

function withDynamicMethodExistsAndVariable(string $method): void {
	$instance = new HelloWorld();
	$callable = [$instance, $method];
	if (!is_callable($callable)) {
		return;
	}

	$callable();
}

function methodExistsInElseBranch(string $method): void {
	$instance = new HelloWorld();
	if (method_exists($instance, $method)) {
		[$instance, $method]();
	}
}
