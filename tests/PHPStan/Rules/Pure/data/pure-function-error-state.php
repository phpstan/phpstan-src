<?php // lint >= 8.0

declare(strict_types = 1);

namespace PureFunctionErrorState;

/** @phpstan-pure */
function leavesErrors(string $json, string $id): bool
{
	$decoded = json_decode($json);
	$transliterator = \Transliterator::create($id);
	$parts = preg_split('/,/', $json);

	return $decoded !== null && $transliterator !== null && $parts !== false;
}

/** @phpstan-pure */
function encodes(mixed $value): string|false
{
	// jsonSerialize() of the encoded objects can do anything
	return json_encode($value);
}

/** @phpstan-pure */
function prints(string $json): mixed
{
	var_dump($json);

	return json_decode($json);
}
