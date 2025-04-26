<?php // lint >= 8.0

namespace Bug8282;

/**
 * @phpstan-type record array{id: positive-int, name: string}
 */
class Collection
{
	/** @param list<record> $list */
	public function __construct(
		public array $list
	)
	{
	}

	public function updateName(int $index, string $name): void
	{
		assert(isset($this->list[$index]));
		$this->list[$index]['name'] = $name;
	}

	public function updateNameById(int $id, string $name): void
	{
		foreach ($this->list as $index => $entry) {
			if ($entry['id'] === $id) {
				$this->list[$index]['name'] = $name;
			}
		}
	}
}
