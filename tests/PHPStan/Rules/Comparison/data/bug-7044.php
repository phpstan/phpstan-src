<?php declare(strict_types = 1);

namespace Bug7044;

class HelloWorld
{
	public const LOWER_PADDING_SIZE = 0;

	private function __construct(){
		//NOOP
	}

	public static function serializeFullChunk() : string{
		//TODO: HACK! fill in fake subchunks to make up for the new negative space client-side
		for($y = 0; $y < self::LOWER_PADDING_SIZE; $y++){

		}

		return "";
	}
}
