<?php // lint >= 8.2

declare(strict_types = 1);

namespace Discussion13395;

use function PHPStan\Testing\assertType;

final readonly class View
{
	public function __construct(
		public string $value,
	) {
	}
}

class HelloWorld
{
	/**
	 * @param non-empty-list<array{
	 *     value: ?string,
	 * }> $rows
	 * @return non-empty-list<View>|null
	 */
	public function fetch(
		array $rows,
	) : ?array {
		return $this->buildViews($rows);
	}

	/**
	 * @param non-empty-list<array{
	 *     value: ?string,
	 * }> $rows
	 * @return non-empty-list<View>|null
	 */
	private function buildViews(array $rows) : ?array
	{
		if ($rows[0]['value'] === null) {
			return null;
		}

		$views = \array_map(
			static function (array $row) : View {
				\assert($row['value'] !== null);
				return new View(
					$row['value'],
				);
			},
			$rows,
		);

		assertType('non-empty-list<Discussion13395\\View>&hasOffsetValue(0, Discussion13395\View)', $views); // could be just non-empty-list<Discussion13395\\View>

		return $views;
	}

	/**
	 * @param non-empty-list<array{
	 *     value: ?string,
	 * }> $rows
	 * @return non-empty-list<View>|null
	 */
	private function buildViews2(array $rows) : ?array
	{
		if ($rows[0]['value'] === null) {
			return null;
		}

		if ($rows[1]['value'] === null) {
			return null;
		}

		$views = \array_map(
			static function (array $row) : View {
				\assert($row['value'] !== null);
				return new View(
					$row['value'],
				);
			},
			$rows,
		);

		assertType('non-empty-list<Discussion13395\View>&hasOffsetValue(0, Discussion13395\View)&hasOffsetValue(1, Discussion13395\View)', $views); // could be just &hasOffsetValue(1, Discussion13395\View)

		return $views;
	}
}
