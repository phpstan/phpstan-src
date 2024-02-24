<?php

namespace Bug8296;

class VerifyLoginTask{

	public static function dumpMemory() : void{
		$dummy = [
			"a" => new stdClass(),
			"b" => true
		];
		self::continueDump($dummy);

		$string = 12345;
		self::stringByRef($string);
	}

	/**
	 * @phpstan-param array<string, object> $objects
	 * @phpstan-param-out array<string, object> $objects
	 */
	private static function continueDump(array &$objects) : void{

	}

	private static function stringByRef(string &$string) : void{

	}
}
