<?php

namespace Levels\Comparison;

class Foo
{

	private const FOO_CONST = 'foo';

	/**
	 * @param \stdClass $object
	 * @param int $int
	 * @param float $float
	 * @param string $string
	 * @param int|string $intOrString
	 * @param int|\stdClass $intOrObject
	 */
	public function doFoo(
		\stdClass $object,
		int $int,
		float $float,
		string $string,
		$intOrString,
		$intOrObject
	)
	{
		$result = $object == $int;
		$result = $object == $float;
		$result = $object == $string;
		$result = $object == $intOrString;
		$result = $object == $intOrObject;

		$result = self::FOO_CONST === 'bar';
	}

	public function doBar(\ffmpeg_movie $movie): void
	{
		$movie->getArtist() === 1;
	}

}
