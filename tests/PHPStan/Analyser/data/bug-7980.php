<?php declare(strict_types = 1);

namespace Bug7980;

final class Value
{
	public function __construct(
		public readonly mixed $value,
		public readonly ?int $ttl,
	) {}
}

function value(): Value|null {
	return rand(0, 1)
		? new Value(
			rand(0, 1) ? 1 : null,
			rand(0, 1) ? 1 : null)
		: null;
}

function test(int $value, ?int $ttl): void {
}

$valueObj = value();

$value = is_int($valueObj?->value) ? $valueObj->value : 0;

test($value, $valueObj?->ttl);
