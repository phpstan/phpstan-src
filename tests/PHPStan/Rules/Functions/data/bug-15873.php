<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15873;

use BackedEnum;

/**
 * @param list<mixed>|array<string, mixed> $parameters
 * @return list<mixed>|array<string, mixed>
 */
function convert(array $parameters): array
{
	foreach ($parameters as &$parameter) {
		if ($parameter instanceof BackedEnum) {
			$parameter = $parameter->value;
		}
	}

	return $parameters;
}

/**
 * @param array<string, int|list<int>|array<string, int>> $data
 * @param list<int> $ids
 * @return array<string, int|list<int>|array<string, int>>
 */
function addList(array $data, array $ids): array
{
	$data['ids'] = $ids;
	$data['map'] = [];

	return $data;
}

/** @return list<string>|list<int>|list<float>|list<bool> */
function wrapped(bool|int|float|string $scalar): array
{
	return [$scalar];
}

/**
 * @param list<string>|list<int>|bool|int|float|string $value
 * @return list<string>|list<int>|list<float>|list<bool>
 */
function wrappedOrList(array|bool|int|float|string $value): array
{
	return is_array($value) ? $value : [$value];
}

/** @param list<mixed>|array<string, mixed> $params */
function takeParams(array $params): void
{
}

/** @param list<mixed>|array<string, mixed> $params */
function joinWithEmptyArray(array $params): void
{
	if (count($params) > 0) {
		// nothing
	}
	takeParams($params);
}

/** @param list<array{key: string, flag?: bool}>|array<\stdClass> $mappings */
function takeMappings(array $mappings): void
{
}

/** @param list<array{key: string}>|array<\stdClass> $mappings */
function nestedWriteAfterIsArray(array $mappings): void
{
	foreach (array_keys($mappings) as $key) {
		if (!is_array($mappings[$key])) {
			throw new \LogicException();
		}
		$mappings[$key]['flag'] = true;
	}
	takeMappings($mappings);
}

/**
 * @param list<mixed>|array<string, mixed> $params
 * @param array<int, int> $ints
 * @return array{list<mixed>|array<string, mixed>, list<int>}
 */
function tipOnlyForOffsetNotAccepted(array $params, array $ints): array
{
	return [$params, $ints];
}
