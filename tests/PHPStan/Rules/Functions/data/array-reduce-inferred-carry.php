<?php // lint >= 8.0

declare(strict_types = 1);

namespace ArrayReduceInferredCarry;

/** @param list<string> $strings */
function doFoo(array $strings): void
{
	$concatenated = array_reduce($strings, fn ($carry, $string) => $carry . $string, 'a');
	$named = array_reduce(array: $strings, callback: fn ($carry, $string) => $carry . $string, initial: 'a');
	$typed = array_reduce($strings, static fn (string $carry, string $string): string => $carry . $string, '');
	$set = array_reduce($strings, static function (array $carry, string $string): array {
		$carry[$string] = true;
		return $carry;
	}, []);
}
