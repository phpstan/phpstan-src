<?php

namespace Bug1861;

use function PHPStan\Testing\assertType;

class Tree
{
	/**
	 * @var self[]
	 */
	private $children = [];

	public function isPath(): void
	{
		switch (count($this->children)) {
			case 0:
				assertType('array()', $this->children);
				break;
			case 1:
				assertType('array<' . self::class . '>&nonEmpty', $this->children);
				assertType(self::class, reset($this->children));
				break;
			default:
				assertType('array<' . self::class . '>&nonEmpty', $this->children);
				break;
		}
	}
}
