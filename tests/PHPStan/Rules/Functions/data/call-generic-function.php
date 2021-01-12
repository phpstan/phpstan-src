<?php

namespace CallGenericFunction;

/**
 * @template A
 * @template B
 * @param int|array<A> $a
 * @param int|array<B> $b
 * @return A[]
 */
function f($a, $b): array {}

function test(): void {
	f(1, 2);
}

/**
 * @template A of \DateTime
 * @param A $a
 * @return A
 */
function g($a) {}

function testg(): void {
	g(new \DateTimeImmutable());
}
