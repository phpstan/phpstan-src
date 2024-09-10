<?php declare(strict_types = 1);

namespace Bug11056;

interface Item {}
class A implements Item {}
class B implements Item {}
class C implements Item {}

/**
 * @template T
 * @param class-string<T>|string $class
 * @return ($class is class-string<T> ? T : mixed)
 */
function createA(string $class) {
	return new $class();
}

/**
 * @template T
 * @param class-string<T> $class
 * @return T
 */
function createB(string $class) {
	return new $class();
}

/**
 * @param Item[] $values
 */
function receive(array $values): void { }

receive(
	array_map(
		createA(...),
		[ A::class, B::class, C::class ]
	)
);

receive(
	array_map(
		createB(...),
		[ A::class, B::class, C::class ]
	)
);

receive(
	array_map(
		static fn($val) => createA($val),
		[ A::class, B::class, C::class ]
	)
);

receive(
	array_map(
		static fn($val) => createB($val),
		[ A::class, B::class, C::class ]
	)
);
