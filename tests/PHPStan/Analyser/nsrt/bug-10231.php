<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10231;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @template TGroupColumnName of array-key
	 * @template TValueColumnName of array-key
	 * @template TArray of array
	 * @param array<TArray> $input
	 * @param TGroupColumnName $groupByColumn
	 * @param TValueColumnName $valueColumnName
	 *
	 * @return array<
	 *   value-of<TArray[TGroupColumnName]>,
	 *   list<value-of<TArray[TValueColumnName]>>
	 * >
	 */
	public static function groupByColumn(array $input, string|int $groupByColumn, string|int $valueColumnName): array
	{
		$output = [];
		foreach ($input as $result) {
			$output[$result[$groupByColumn]][] = $result[$valueColumnName];
		}

		return $output;
	}
}

/** @var array<array{event_id: string, id: int}> $input */
$input = [
	['event_id' => '111', 'id' => 1],
	['event_id' => '111', 'id' => 2],
	['event_id' => '222', 'id' => 99],
];

$result = HelloWorld::groupByColumn(
	$input,
	'event_id',
	'id',
);

assertType('array<string, list<int>>', $result);
