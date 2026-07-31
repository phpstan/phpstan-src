<?php declare(strict_types = 1);

namespace Bug13114Return;

class C {
	static function f(): void {}
}

/**
 * @return callable&array<mixed>
 */
function returnsCallableArray(): array
{
	return [new C, 'h'];
}

/**
 * @return callable&array<mixed>
 */
function returnsValidCallableArray(): array
{
	return [C::class, 'f'];
}
