<?php

namespace Bug4666;

class HelloWorld
{
	private const CONST_1 = 'xxx';
	private const CONST_2 = 'yyy';

	/** @param array<MyObject> $objects */
	public function test(array $objects): void
	{
		$types = [];
		foreach ($objects as $object) {
			if (self::CONST_1 === $object->getType() && !in_array(self::CONST_2, $types, true)) {
				$types[] = self::CONST_2;
			}
		}
	}
}

class MyObject
{
	/** @var string */
	private $type;
	public function getType(): string{
		return $this->type;
	}
}
