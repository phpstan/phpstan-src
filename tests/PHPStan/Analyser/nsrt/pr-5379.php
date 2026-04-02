<?php

declare(strict_types=1);

namespace PR5379;

use ArrayAccess;

use function PHPStan\Testing\assertType;

class AggregationParser
{
	/**
	 * @param array<string, mixed> $aggregation
	 */
	private function parseAggregation(array $aggregation)
	{
		$type = $aggregation['type'] ?? null;
		if (!\is_string($type) || empty($type) || is_numeric($type)) {
			return null;
		}

		if (empty($aggregation['field']) && $type !== 'filter') {
			return null;
		}

		$field = '';
		if ($type !== 'filter') {
			$field = self::buildFieldName();
		}

		assertType('non-falsy-string', $type);
	}

	private static function buildFieldName(): string
	{
		return 'field';
	}
}

class AggregationParser2
{
	private function parseAggregation(string $aggregation, string $type)
	{
		if (empty($aggregation[1]) && $type !== 'filter') {
			return null;
		}
		assertType('string', $type);

		if ($type !== 'filter') {
			assertType('string', $type);
		}

		assertType('string', $type);
	}

	private function parseAggregation2(ArrayAccess $aggregation, string $type)
	{
		if (empty($aggregation['foo']) && $type !== 'filter') {
			return null;
		}
		assertType('string', $type);

		if ($type !== 'filter') {
			assertType('string', $type);
		}

		assertType('string', $type);
	}
}
