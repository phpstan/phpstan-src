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
				assertType('array{}', $this->children);
				break;
			case 1:
				assertType('non-empty-array<' . self::class . '>', $this->children);
				assertType(self::class, reset($this->children));
				break;
			default:
				assertType('non-empty-array<' . self::class . '>', $this->children);
				break;
		}
	}
}
