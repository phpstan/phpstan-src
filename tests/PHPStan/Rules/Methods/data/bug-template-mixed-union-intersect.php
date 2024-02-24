<?php declare(strict_types=1); // lint >= 8.0

namespace BugTemplateMixedUnionIntersect;

interface FooInterface
{
	public function foo(): int;
}

/**
 * @template T of mixed
 * @param T $a
 */
function foo(mixed $a, FooInterface $b, mixed $c): void
{
	if ($a instanceof FooInterface) {
		var_dump($a->bar());
	}
	if ($c instanceof FooInterface) {
		var_dump($c->bar());
	}
	$d = rand() > 1 ? $a : $b;
	var_dump($d->foo());
	$d = rand() > 1 ? $c : $b;
	var_dump($d->foo());
}
