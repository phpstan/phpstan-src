<?php

namespace Bug13035;

use function PHPStan\debugScope;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @var list<int>
	 */
	public array $list = [];

	public function bug(int $offset): void
	{
		if (isset($this->list[$offset])) {
			$this->list[$offset] = 123;
		}
	}
}
