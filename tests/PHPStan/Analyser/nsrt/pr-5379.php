<?php

declare(strict_types=1);

namespace PR5379;

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
