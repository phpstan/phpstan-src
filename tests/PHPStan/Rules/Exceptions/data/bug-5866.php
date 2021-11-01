<?php // lint >= 8.0

namespace Bug5866;

use InvalidArgumentException;
use JsonException;

class Foo
{

	/**
	 * @param string $contents
	 */
	public function read($contents) : Response {
		try {
			$parsed = json_decode($contents, true, flags: JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw new InvalidArgumentException('Unable to decode contents');
		}
	}

}
