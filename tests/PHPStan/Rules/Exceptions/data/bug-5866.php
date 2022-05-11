<?php // lint >= 8.0

namespace Bug5866;

use InvalidArgumentException;
use JsonException;

class Foo
{

	/**
	 * @param string $contents
	 */
	public function decode($contents) {
		try {
			$parsed = json_decode($contents, true, flags: JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw new InvalidArgumentException('Unable to decode contents');
		}
	}

	/**
	 * @param string $contents
	 */
	public function decode2($contents) {
		try {
			$parsed = json_decode($contents, depth: 123, flags: JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR, associative: true,);
		} catch (JsonException $exception) {
			throw new InvalidArgumentException('Unable to decode contents');
		}
	}

	/**
	 * @param string $contents
	 */
	public function encode($contents) {
		try {
			$encoded = json_encode($contents, depth: 2, flags: JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw new InvalidArgumentException('Unable to encode contents');
		}
	}

}
