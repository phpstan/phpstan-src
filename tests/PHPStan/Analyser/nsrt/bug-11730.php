<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug11730;

use function PHPStan\Testing\assertType;

class Foo {
	public bool $active = false;
}

/** @return ($value is Foo ? true : false) */
function isFoo(mixed $value): bool {
	return $value instanceof Foo;
}

/** @phpstan-assert-if-true Foo $value */
function checkFoo(mixed $value): bool {
	return $value instanceof Foo;
}

$data = [new Foo, new Foo];

assertType('array{Bug11730\Foo, Bug11730\Foo}', array_filter($data, isFoo(...)));
assertType('array{Bug11730\Foo, Bug11730\Foo}', array_filter($data, checkFoo(...)));

/** @param array{Foo|int, Foo|int} $data */
function doFoo ($data) {
	assertType('array{0?: Bug11730\Foo, 1?: Bug11730\Foo}', array_filter($data, isFoo(...)));
	assertType('array{0?: Bug11730\Foo, 1?: Bug11730\Foo}', array_filter($data, checkFoo(...)));
}

class Asserter {
	/** @return ($value is Foo ? true : false) */
	function isFoo(mixed $value): bool {
		return $value instanceof Foo;
	}

	/** @phpstan-assert-if-true Foo $value */
	function checkFoo(mixed $value): bool {
		return $value instanceof Foo;
	}

	/** @return ($value is Foo ? true : false) */
	static function isFooStatic(mixed $value): bool {
		return $value instanceof Foo;
	}

	/** @phpstan-assert-if-true Foo $value */
	static function checkFooStatic(mixed $value): bool {
		return $value instanceof Foo;
	}
}

function doBar(): void {
	$data = [new Foo, new Foo];
	$asserter = new Asserter;

	assertType('array{Bug11730\Foo, Bug11730\Foo}', array_filter($data, $asserter->isFoo(...)));
	assertType('array{Bug11730\Foo, Bug11730\Foo}', array_filter($data, $asserter->checkFoo(...)));

	assertType('array{Bug11730\Foo, Bug11730\Foo}', array_filter($data, Asserter::isFooStatic(...)));
	assertType('array{Bug11730\Foo, Bug11730\Foo}', array_filter($data, Asserter::checkFooStatic(...)));
}

/** @param array{Foo|int, Foo|int} $data */
function doBaz(array $data): void {
	$asserter = new Asserter;

	assertType('array{0?: Bug11730\Foo, 1?: Bug11730\Foo}', array_filter($data, $asserter->isFoo(...)));
	assertType('array{0?: Bug11730\Foo, 1?: Bug11730\Foo}', array_filter($data, $asserter->checkFoo(...)));

	assertType('array{0?: Bug11730\Foo, 1?: Bug11730\Foo}', array_filter($data, Asserter::isFooStatic(...)));
	assertType('array{0?: Bug11730\Foo, 1?: Bug11730\Foo}', array_filter($data, Asserter::checkFooStatic(...)));
}

class CallableAsserterConditionalReturn {
	/** @return ($value is Foo ? true : false) */
	function __invoke(mixed $value): bool {
		return $value instanceof Foo;
	}
}

$data = [new Foo, new Foo];
assertType('array<0|1, Bug11730\Foo>', array_filter($data, new CallableAsserterConditionalReturn())); // could be array{Foo, Foo}

class CallableAssertIfTrue {
	/** @phpstan-assert-if-true Foo $value */
	function __invoke(mixed $value): bool {
		return $value instanceof Foo;
	}
}

$data = [new Foo, new Foo];
assertType('array<0|1, Bug11730\Foo>', array_filter($data, new CallableAssertIfTrue())); // could be array{Foo, Foo}

/** @phpstan-assert-if-true =Foo $value */
function checkFooAndMore(mixed $value): bool {
	return $value instanceof Foo && $value->active;
}

$data = [new Foo, new Foo];
assertType('array{0?: Bug11730\Foo, 1?: Bug11730\Foo}', array_filter($data, checkFooAndMore(...)));
