<?php declare(strict_types = 1);

namespace Bug3842;

class ClassA
{
	public static function callback(): void
	{
	}
}

class ClassB
{
	public function callback(): void
	{
	}
}

function callback(callable $value): void {
	if (is_array($value)) {
		check($value);
	}
}
/** @param array{string|object, string} $values */
function check(array $values): void {
}

callback([ClassA::class, 'callback']);
callback([new ClassB, 'callback']);
