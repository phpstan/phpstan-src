<?php

namespace Bug6552;

class HelloWorld
{
	/**
	 * @param mixed $a
	 * @return array{schemaVersion: mixed}|null
	 */
	public function sayHello($a)
	{
		if (!\is_array($a) || !\array_key_exists('schemaVersion', $a)) {
			return null;
		}

		return $a;
	}
}
