<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15424;

final class Dekking
{
	public function __construct(public float $amount)
	{
	}
}

function nullableReceiver(?Dekking $data = null): ?string
{
	if ($data->amount === null) {
		return null;
	}

	return 'x';
}

function nonNullableReceiver(Dekking $data): ?string
{
	if ($data->amount === null) {
		return null;
	}

	return 'x';
}
