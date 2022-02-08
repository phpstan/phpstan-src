<?php declare(strict_types = 1);

namespace Bug6117;

final class Mapping
{
}

final class Stuff
{
	/**
	 * @var string
	 */
	const CATEGORY_TYPE_ONE = '44';

	/**
	 * @var string
	 */
	const CATEGORY_TYPE_TWO = '45';

	/**
	 * @var array<self::CATEGORY_*, Mapping>
	 */
	private $mappings = [];

	public function testMe(): void
	{
		$this->mappings[self::CATEGORY_TYPE_TWO] = new Mapping();

		$this->mappings[(string)self::CATEGORY_TYPE_TWO] = new Mapping();
	}
}
