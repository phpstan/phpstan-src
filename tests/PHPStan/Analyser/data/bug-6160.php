<?php declare(strict_types = 1);

namespace Bug6160;

const PREG_SPLIT_NO_EMPTY_COPY = PREG_SPLIT_NO_EMPTY;

class HelloWorld
{
     /**
	  * @param PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE|0    $flags PREG_SPLIT_NO_EMPTY or PREG_SPLIT_DELIM_CAPTURE
      * @return list<string>
      */
     public static function split($flags = 0){
		 return [];
	 }

	 public static function test(): void
	 {
		 self::split(94561); // should error
		 self::split(PREG_SPLIT_NO_EMPTY); // should work
		 self::split(PREG_SPLIT_DELIM_CAPTURE); // should work
		 self::split(PREG_SPLIT_NO_EMPTY_COPY); // should work
		 self::split("sdf"); // should error
	 }
}
