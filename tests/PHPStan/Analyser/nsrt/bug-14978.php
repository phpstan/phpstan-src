<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14978;

use function PHPStan\Testing\assertType;

enum Locale: string
{
	case A = 'a';
	case B = 'b';
	case C = 'c';
	case D = 'd';
}

/**
 * @param array<string, list<string>> $paths
 * @return array<array-key, mixed>
 */
function catalog(array $paths): array
{
	$catalog = [];

	foreach ($paths as $locale => $files) {
		$locale = Locale::from($locale)->value;
		assertType("'a'|'b'|'c'|'d'", $locale);
		$catalog[$locale] ??= [];

		foreach ($files as $file) {
			$catalog[$locale] = setByKey($catalog[$locale], $file, $file);
		}
	}

	return $catalog;
}

/**
 * @param array<array-key, mixed> $array
 * @return array<array-key, mixed>
 */
function setByKey(array $array, string $key, mixed $value): array
{
	$array[$key] = $value;

	return $array;
}

/**
 * @param 'a'|'b'|'c'|'d' $x
 * @param 'a'|'b'|'c'|'d' $y
 */
function compareUnions(string $x, string $y): void
{
	if ($x === $y) {
		assertType("'a'|'b'|'c'|'d'", $x);
	}
}
