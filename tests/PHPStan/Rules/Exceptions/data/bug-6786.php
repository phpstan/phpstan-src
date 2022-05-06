<?php // lint >= 7.4

declare(strict_types = 1);

namespace Bug6786;

class HelloWorld
{
	protected int $id;
	protected string $code;
	protected bool $suggest;

	/**
	 * @param array|mixed[] $row
	 * @return void
	 */
	protected function mapping(array $row): void
	{
		$this->id = (int) $row['id'];
		$this->code = $row['code'];
		$this->suggest = (bool) $row['suggest'];
	}

}
