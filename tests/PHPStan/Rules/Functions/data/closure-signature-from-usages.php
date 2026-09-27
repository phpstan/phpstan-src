<?php // lint >= 8.0

namespace ClosureSignatureFromUsagesRule;

/** @param callable(int): int $cb */
function takesIntCallback(callable $cb): void
{
}

/** @param callable(int): string $cb */
function takesIntToStringCallback(callable $cb): void
{
}

function register(callable $cb): void
{
}

function (): void {
	$invoked = function ($str) {
		return strlen($str);
	};
	$invoked(1);

	$sent = fn ($str) => strlen($str);
	takesIntCallback($sent);

	$escaped = function ($str) {
		return strlen($str);
	};
	$escaped(1);
	register($escaped);

	$identity = function ($x) {
		return $x;
	};
	takesIntToStringCallback($identity);
};

/**
 * @template T of \BackedEnum|int|string
 * @param \Closure(T): string $cb
 * @return list<T>
 */
function takesConvertor(\Closure $cb): array
{
	return [];
}

function (): void {
	$convertor = static function (int|string $value): string {
		return 'x' . $value;
	};
	takesConvertor($convertor);

	$inner = static fn (string $a, float $b): array => [$a, $b];
	$outer = static fn (mixed ...$args): array => $inner(...$args);
	$outer('x', 1.0);
};
