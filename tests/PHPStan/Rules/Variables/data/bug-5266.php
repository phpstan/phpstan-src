<?php

namespace Bug5266;

class TestEnums
{
	const SORT_ASC = 'sort_asc';
	const SORT_DESC = 'sort_desc';

	/**
	 * @param self::SORT_ASC|self::SORT_DESC $sortType
	 */
	public function sort(string $sortType): void
	{
		if ($sortType === self::SORT_ASC) {
			$message = 'Sorting ASC';
		} elseif ($sortType === self::SORT_DESC) {
			$message = 'Sorting DESC';
		}

		echo $message . '\n';
	}
}
