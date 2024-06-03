<?php declare(strict_types = 1);

namespace Bug10721;

use function PHPStan\Testing\assertType;

final class HandpickedWordlistProvider
{
	/**
	 * @return non-empty-list<non-empty-string>
	 */
	public function retrieve(?int $limit = 20): array
	{
		$list = [
			'zib',
			'zib 2',
			'zeit im bild',
			'soko',
			'landkrimi',
			'tatort',
		];

		assertType("array{'zib', 'zib 2', 'zeit im bild', 'soko', 'landkrimi', 'tatort'}", $list);
		shuffle($list);
		assertType("non-empty-array<0|1|2|3|4|5, 'landkrimi'|'soko'|'tatort'|'zeit im bild'|'zib'|'zib 2'>&list", $list);

		assertType("non-empty-array<0|1|2|3|4|5, 'landkrimi'|'soko'|'tatort'|'zeit im bild'|'zib'|'zib 2'>&list", array_slice($list, 0, max($limit, 1)));
		return array_slice($list, 0, max($limit, 1));
	}
}
