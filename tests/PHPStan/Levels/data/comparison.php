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
		$results = [];
		$results[] = $object == $int;
		$results[] = $object == $float;
		$results[] = $object == $string;
		$results[] = $object == $intOrString;
		$results[] = $object == $intOrObject;

		$results[] = self::FOO_CONST === 'bar';
		var_dump($results);
	}

	public function doBar(\ffmpeg_movie $movie): void
	{
		$movie->getArtist() === 1;
	}

}
