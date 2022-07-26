<?php

namespace Bug6181;

class Foo
{
	const KEY_VERSION = 'version';
	const KEY_PAYLOAD = 'payload';

	/**
	 * @param array{version?: string, payload?: string} $array
	 */
	public function foo(array $array): void
	{
		if (false === array_key_exists(static::KEY_VERSION, $array)) {
			throw new \InvalidArgumentException(sprintf("Array is missing required key '%s'.", static::KEY_VERSION));
		}

		if (false === array_key_exists(static::KEY_PAYLOAD, $array)) {
			throw new \InvalidArgumentException(sprintf("Array is missing required key '%s'.", static::KEY_PAYLOAD));
		}
	}
}
