<?php

namespace Bug2268;

abstract class Message implements \ArrayAccess
{
	/**
	 * @param string $value
	 */
	abstract public function offsetSet($key, $value);
}


function test(Message $data)
{
	if (isset($data['name'])) {
		$data['name'] = 1;
	}

	test($data);
}
