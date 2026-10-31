<?php declare(strict_types = 1);

namespace Bug15491;

use function PHPStan\Testing\assertType;

function idFromAlias(string $alias): ?string
{
	return strpos($alias, '.') !== false ? md5($alias) : null;
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function withCoalesce(array $data): array
{
	$data['ids'] = array_filter(
		array_map(
			function (string $alias): ?string {
				$id = idFromAlias($alias);
				if ($id === null) {
					echo 'invalid';
				}
				return $id;
			},
			explode('|', (string) ($data['options'] ?? ''))
		)
	);
	assertType('array<int<0, max>, non-falsy-string>', $data['ids']);

	return $data;
}

/**
 * @template T
 * @param T $x
 * @return T
 */
function identity($x)
{
	return $x;
}

/**
 * @param mixed $x
 * @return list<string>
 */
function strings($x): array
{
	return [];
}

/** @param array<string, mixed> $data */
function assignOp(array $data): void
{
	$x = identity(array_map(
		function (string $alias): int {
			$i = 0;
			$i += strlen($alias);
			if ($i === 0) {
				echo 'empty';
			}
			return $i;
		},
		strings($data['options'] ?? '')
	));
	assertType('list<int<0, max>>', $x);
}

/** @param array<string, mixed> $data */
function coalesceAssign(array $data): void
{
	$x = identity(array_map(
		function (string $alias): ?string {
			$id = null;
			$id ??= idFromAlias($alias);
			if ($id === null) {
				echo 'invalid';
			}
			return $id;
		},
		strings($data['options'] ?? '')
	));
	assertType('list<string|null>', $x);
}

/** @param array<string, mixed> $data */
function increment(array $data): void
{
	$x = identity(array_map(
		function (string $alias): int {
			$i = 0;
			$i++;
			if ($i === 0) {
				echo 'never';
			}
			return $i;
		},
		strings($data['options'] ?? '')
	));
	assertType('list<1>', $x);
}

/** @param array<string, mixed> $data */
function byRefOutParameter(array $data): void
{
	$x = identity(array_map(
		function (string $alias): ?string {
			$m = null;
			preg_match('/a/', $alias, $m);
			if ($m === null) {
				echo 'never';
			}
			return $m[0] ?? null;
		},
		strings($data['options'] ?? '')
	));
	assertType('list<non-empty-string|null>', $x);
}

/** @param array<string, mixed> $data */
function listAssign(array $data): void
{
	$x = identity(array_map(
		function (string $alias): ?string {
			[$id] = [idFromAlias($alias)];
			if ($id === null) {
				echo 'invalid';
			}
			return $id;
		},
		strings($data['options'] ?? '')
	));
	assertType('list<string|null>', $x);
}

/** @param array<string, mixed> $data */
function arrowFunction(array $data): void
{
	$x = identity(array_map(
		fn (string $alias): ?string => ($id = idFromAlias($alias)) === null ? $id : $id,
		strings($data['options'] ?? '')
	));
	assertType('list<string|null>', $x);
}

/** @param array<string, mixed> $data */
function arrayFilterCallback(array $data): void
{
	$x = identity(array_filter(
		strings($data['options'] ?? ''),
		function (string $alias): bool {
			$id = idFromAlias($alias);
			if ($id === null) {
				echo 'invalid';
			}
			return $id !== null;
		}
	));
	assertType('array<int<0, max>, string>', $x);
}
