<?php declare(strict_types = 1);

namespace Bug7857;

class Paginator
{
	/**
	 * @return array{page: int, perPage?: int}
	 */
	public function toArray(int $page, ?int $perPage): array
	{
		return array_merge(
			['page' => $page],
			$perPage !== null ? ['perPage' => $perPage] : []
		);
	}
}
