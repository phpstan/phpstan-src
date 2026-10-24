<?php declare(strict_types = 1);

namespace Bug15491Rule;

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

	return $data;
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function withoutCoalesce(array $data): array
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
			explode('|', (string) $data['options'])
		)
	);

	return $data;
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function withoutIf(array $data): array
{
	$data['ids'] = array_filter(
		array_map(
			function (string $alias): ?string {
				$id = idFromAlias($alias);
				return $id;
			},
			explode('|', (string) ($data['options'] ?? ''))
		)
	);

	return $data;
}
