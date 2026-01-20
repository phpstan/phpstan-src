<?php // lint >= 8.4

namespace Bug13980;

final class PageInfo
{
	public ?string $endCursor {
		get => $this->endCursor ??= $this->data['endCursor'];
	}

	/**
	 * @param array{
	 *     'endCursor': null|string,
	 *     'hasNextPage': bool,
	 * } $data
	 */
	public function __construct(
		private readonly array $data,
	) {}
}

class Test {
	public int $test {
		get => $this->test ??= random_int(PHP_INT_MIN, PHP_INT_MAX);
	}
}
