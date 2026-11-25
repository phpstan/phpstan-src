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

/** @phpstan-pure */
function readsGlobalState(string $file): string|false
{
	// the result changes after header(), clearstatcache(), ini_set() or json_decode()
	return headers_list() !== [] && is_file($file) ? ini_get('precision') : json_last_error_msg();
}
