<?php

namespace Bug15322;

/**
 * @phpstan-all-methods-pure
 * @method int magic()
 */
class MagicMethods
{
}

class Test
{
	/** @phpstan-pure */
	public function run(MagicMethods $object): int
	{
		return $object->magic();
	}
}
