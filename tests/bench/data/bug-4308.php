<?php // lint >= 8.0

namespace Bug4308;

class Test
{
	/**
	 * @var (string|int|null)[]
	 * @phpstan-var array{
	 *  prop1?: string, prop2?: string, prop3?: string,
	 *  prop4?: string, prop5?: string, prop6?: string,
	 *  prop7?: string, prop8?: int, prop9?: int
	 * }
	 */
	protected array $updateData = [];

	/**
	 * @phpstan-param array{
	 *  prop1?: string, prop2?: string, prop3?: string,
	 *  prop4?: string, prop5?: string, prop6?: string,
	 *  prop7?: string, prop8?: int, prop9?: int
	 * } $data
	 */
	public function update(array $data): void
	{
		$this->updateData = $data + $this->updateData;
	}
}
