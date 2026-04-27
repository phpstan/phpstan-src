<?php declare(strict_types = 1);

namespace Bug4510;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function existingMethod(): void {}

	public function doSomething(string $method): void {
		if (!method_exists($this, $method)) {
			return;
		}

		[$this, $method](); // error - method_exists doesn't imply callable
	}
}

function testMethodExists(string $method): void {
	$instance = new HelloWorld();
	if (!method_exists($instance, $method)) {
		return;
	}

	assertType('array{Bug4510\HelloWorld, string}', [$instance, $method]);
	[$instance, $method](); // error - method_exists doesn't imply callable
}

function testIsCallableInlineArray(string $method): void {
	$instance = new HelloWorld();
	if (!is_callable([$instance, $method])) {
		return;
	}

	assertType('list{Bug4510\HelloWorld, string}&callable(): mixed', [$instance, $method]);
	[$instance, $method](); // ok - is_callable verifies callability
}

function testMethodExistsWithClassString(string $method): void {
	if (!method_exists(HelloWorld::class, $method)) {
		return;
	}

	assertType("array{'Bug4510\\\\HelloWorld', string}", [HelloWorld::class, $method]);
	[HelloWorld::class, $method](); // error - method_exists doesn't imply callable
}

function testIsCallableWithClassString(string $method): void {
	if (!is_callable([HelloWorld::class, $method])) {
		return;
	}

	assertType("list{'Bug4510\\\\HelloWorld', string}&callable(): mixed", [HelloWorld::class, $method]);
	[HelloWorld::class, $method](); // ok - is_callable verifies callability
}

function testIsCallableExplicitKeys(string $method): void {
	$instance = new HelloWorld();
	if (!is_callable([0 => $instance, 1 => $method])) {
		return;
	}

	assertType('list{Bug4510\HelloWorld, string}&callable(): mixed', [0 => $instance, 1 => $method]);
	[0 => $instance, 1 => $method](); // ok - is_callable verifies callability
}

function testIsCallableExplicitKeysWithClassString(string $method): void {
	if (!is_callable([0 => HelloWorld::class, 1 => $method])) {
		return;
	}

	assertType("list{'Bug4510\\\\HelloWorld', string}&callable(): mixed", [0 => HelloWorld::class, 1 => $method]);
	[0 => HelloWorld::class, 1 => $method](); // ok - is_callable verifies callability
}

function testWithDynamicMethodExistsAndVariable(string $method): void {
	$instance = new HelloWorld();
	$callable = [$instance, $method];
	if (!is_callable($callable)) {
		return;
	}

	$callable(); // ok - is_callable on variable already worked
}

function testMethodExistsInElseBranch(string $method): void {
	$instance = new HelloWorld();
	if (method_exists($instance, $method)) {
		[$instance, $method](); // error - method_exists doesn't imply callable
	}
}

function testIsCallableInElseBranch(string $method): void {
	$instance = new HelloWorld();
	if (is_callable([$instance, $method])) {
		[$instance, $method](); // ok - is_callable verifies callability
	}
}

function testNoMethodExists(string $method): void {
	$instance = new HelloWorld();
	assertType('array{Bug4510\HelloWorld, string}', [$instance, $method]);
}
