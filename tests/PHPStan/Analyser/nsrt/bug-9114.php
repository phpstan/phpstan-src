<?php declare(strict_types = 1);

namespace Bug9114;

use function PHPStan\Testing\assertType;

function foo(string $foo): void
{
	if ('' === $foo || '0' === $foo) {
		throw new \Exception();
	}
	assertType('non-falsy-string', $foo);
}

function bar(string $foo): void
{
	if ('0' === $foo || '' === $foo) {
		throw new \Exception();
	}
	assertType('non-falsy-string', $foo);
}

function baz(string $foo): void
{
	if ('0' === $foo) {
		throw new \Exception();
	}
	assertType('string', $foo);
	if ('' === $foo) {
		throw new \Exception();
	}
	assertType('non-empty-string', $foo);
}

function qux(string $foo): void
{
	if ('' !== $foo && '0' !== $foo) {
		assertType('non-falsy-string', $foo);
	}
}

function quux(string $foo): void
{
	if ('0' !== $foo && '' !== $foo) {
		assertType('non-falsy-string', $foo);
	}
}
