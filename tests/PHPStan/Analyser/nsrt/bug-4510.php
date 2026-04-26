<?php declare(strict_types = 1);

namespace Bug4510Nsrt;

use function PHPStan\Testing\assertType;

class Foo
{
	public function existingMethod(): void {}
}

function testMethodExists(string $method): void {
	$instance = new Foo();
	if (!method_exists($instance, $method)) {
		return;
	}

	assertType('list{Bug4510Nsrt\Foo, string}&callable(): mixed', [$instance, $method]);
}

function testIsCallableInlineArray(string $method): void {
	$instance = new Foo();
	if (!is_callable([$instance, $method])) {
		return;
	}

	assertType('list{Bug4510Nsrt\Foo, string}&callable(): mixed', [$instance, $method]);
}

function testMethodExistsWithClassString(string $method): void {
	if (!method_exists(Foo::class, $method)) {
		return;
	}

	assertType("list{'Bug4510Nsrt\\\\Foo', string}&callable(): mixed", [Foo::class, $method]);
}

function testNoMethodExists(string $method): void {
	$instance = new Foo();
	assertType('array{Bug4510Nsrt\Foo, string}', [$instance, $method]);
}
