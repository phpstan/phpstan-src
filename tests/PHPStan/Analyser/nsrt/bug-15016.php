<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15016;

use function PHPStan\Testing\assertType;

/** @template-covariant T of bool = bool */
final class Decimal
{

	/** @phpstan-assert-if-false self<true> $this */
	public function isZero(): bool
	{
		return true;
	}

	/** @phpstan-assert-if-true self<true> $this */
	public function isNotZero(): bool
	{
		return true;
	}

	/** @phpstan-assert self<true> $this */
	public function assertZero(): void
	{
	}

	/** @phpstan-assert-if-false self<true> $other */
	public function isZeroOf(Decimal $other): bool
	{
		return true;
	}

	/** @phpstan-assert-if-false self<true> $other */
	public static function isZeroOfStatic(Decimal $other): bool
	{
		return true;
	}

	/**
	 * @param mixed $x
	 * @return ($x is int ? true : false)
	 */
	public function isInt($x): bool
	{
		return is_int($x);
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

function assertIfFalse(?Subscription $lastPeriod): void
{
	if ($lastPeriod?->price->isZero()) {
		assertType('Bug15016\Subscription', $lastPeriod);
		assertType('Bug15016\\Decimal~Bug15016\\Decimal<true>', $lastPeriod?->price);
	} else {
		assertType('Bug15016\Subscription|null', $lastPeriod);
		assertType('Bug15016\\Decimal|null', $lastPeriod?->price);
	}
}

function assertIfFalseStrictlyFalse(?Subscription $lastPeriod): void
{
	if ($lastPeriod?->price->isZero() === false) {
		assertType('Bug15016\Subscription', $lastPeriod);
		assertType('Bug15016\Decimal<true>', $lastPeriod?->price);
	}
}

function assertIfTrue(?Subscription $lastPeriod): void
{
	if ($lastPeriod?->price->isNotZero()) {
		assertType('Bug15016\Subscription', $lastPeriod);
		assertType('Bug15016\Decimal<true>', $lastPeriod?->price);
	} else {
		assertType('Bug15016\Subscription|null', $lastPeriod);
	assertType('Bug15016\\Decimal|null', $lastPeriod?->price);
	}
}

function assertUnconditionally(?Subscription $lastPeriod): void
{
	$lastPeriod?->price->assertZero();
	assertType('Bug15016\Subscription|null', $lastPeriod);
		assertType('Bug15016\\Decimal|null', $lastPeriod?->price);
}

function assertOnParameter(?Subscription $lastPeriod, Decimal $other): void
{
	if ($lastPeriod?->price->isZeroOf($other)) {
		assertType('Bug15016\\Decimal~Bug15016\\Decimal<true>', $other);
	} else {
		assertType('Bug15016\\Decimal', $other);
	}
}

function assertOnParameterOfStaticCall(?Subscription $lastPeriod, Decimal $other): void
{
	if ($lastPeriod?->price::isZeroOfStatic($other)) {
		assertType('Bug15016\\Decimal~Bug15016\\Decimal<true>', $other);
	} else {
		assertType('Bug15016\\Decimal', $other);
	}
}

function conditionalReturnType(?Subscription $lastPeriod, mixed $x): void
{
	if ($lastPeriod?->price->isInt($x)) {
		assertType('int', $x);
	} else {
		assertType('mixed', $x);
	}
}

function nonNullableReceiverStillNarrows(Subscription $lastPeriod, Decimal $other): void
{
	if ($lastPeriod?->price->isZeroOf($other)) {
		assertType('Bug15016\\Decimal~Bug15016\\Decimal<true>', $other);
	} else {
		assertType('Bug15016\Decimal<true>', $other);
	}
}
