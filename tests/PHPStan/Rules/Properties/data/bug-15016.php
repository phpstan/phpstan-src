<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15016NullsafeProperty;

/** @template-covariant T of bool = bool */
final class Decimal
{

	/** @phpstan-assert-if-false self<true> $this */
	public function isZero(): bool
	{
		return true;
	}

}

final class Subscription
{

	public Decimal $price;

}

/** @return array<string, mixed> */
function test(?Subscription $lastPeriod): array
{
	return [
		'a' => $lastPeriod?->price->isZero() ? 'free' : 'paid',
		'b' => $lastPeriod?->price,
	];
}
