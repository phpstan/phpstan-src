<?php // lint >= 8.0

namespace Bug15837;

use RuntimeException;
use function PHPStan\Testing\assertType;

final class Example
{
	/** @throws ($fail is true ? RuntimeException : void) */
	public static function run(bool $fail = true): void
	{
		if ($fail) {
			throw new RuntimeException();
		}
	}
}

function direct(): void
{
	$result = false;
	try {
		Example::run(false);
		$result = true;
	} catch (RuntimeException) {
	}
	assertType('true', $result);
}

function indirect(): void
{
	$class = Example::class;
	$result = false;
	try {
		$class::run(false);
		$result = true;
	} catch (RuntimeException) {
	}
	assertType('true', $result);
}

function throughObject(Example $example): void
{
	$result = false;
	try {
		$example::run(false);
		$result = true;
	} catch (RuntimeException) {
	}
	assertType('true', $result);
}

/** @param class-string<Example> $class */
function throughClassString(string $class): void
{
	$result = false;
	try {
		$class::run(false);
		$result = true;
	} catch (RuntimeException) {
	}
	assertType('true', $result);
}

function indirectMayThrow(): void
{
	$class = Example::class;
	$result = false;
	try {
		$class::run();
		$result = true;
	} catch (RuntimeException) {
	}
	assertType('bool', $result);
}

final class ExampleConstructor
{
	/** @throws ($fail is true ? RuntimeException : void) */
	public function __construct(bool $fail = true)
	{
		if ($fail) {
			throw new RuntimeException();
		}
	}
}

function directNew(): void
{
	$result = false;
	try {
		new ExampleConstructor(false);
		$result = true;
	} catch (RuntimeException) {
	}
	assertType('true', $result);
}

function indirectNew(): void
{
	$class = ExampleConstructor::class;
	$result = false;
	try {
		new $class(false);
		$result = true;
	} catch (RuntimeException) {
	}
	assertType('true', $result);
}

function indirectNewMayThrow(): void
{
	$class = ExampleConstructor::class;
	$result = false;
	try {
		new $class();
		$result = true;
	} catch (RuntimeException) {
	}
	assertType('bool', $result);
}
