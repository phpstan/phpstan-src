<?php

namespace Bug8288;

use Stringable;

class Bug8288 {

	/**
	 * @param numeric-string $numericString
	 */
	public function opMod(int $int, float $float, string $string, string $numericString, Stringable $stringable, array $array)
	{
		# Safe
		$int % $int;
		# Deprecated in PHP 8.1
		$int % $float;
		$int % $numericString;
		$float % $int;
		$float % $float;
		$float % $numericString;
		$numericString % $int;
		$numericString % $float;
		$numericString % $numericString;
		# Not safe
		$int % $string;
		$int % $stringable;
		$int % $array;
		$float % $string;
		$float % $stringable;
		$float % $array;
		$string % $int;
		$string % $float;
		$string % $string;
		$string % $numericString;
		$string % $stringable;
		$string % $array;
		$numericString % $string;
		$numericString % $stringable;
		$numericString % $array;
		$stringable % $int;
		$stringable % $float;
		$stringable % $string;
		$stringable % $numericString;
		$stringable % $stringable;
		$stringable % $array;
		$array % $int;
		$array % $float;
		$array % $string;
		$array % $numericString;
		$array % $stringable;
		$array % $array;

		// The following three examples should all be flagged as resulting in implicit float to int conversion with potential loss of precision. This behaviour is deprecated in PHP 8.1, and results in a deprecation warning (if deprecation warnings are enabled, which they are by default PHP 8+), if encountered at runtime. It would be helpful if phpstan could catch and flag such cases during static analysis.
		$singleByteCode = ord('a');
		$float1 = ( $singleByteCode / 16 );
		// This is not safe
		$float1 % 15;
		( 97 / 16 ) % 15;
		6.0625 % 15;

		// The following three examples should not result in any error
		$singleByteCode = ord('a');
		$int1 = intdiv( $singleByteCode, 16 );
		// This is safe
		$int1 % 15;
		intdiv( 97, 16 ) % 15;
		6 % 15;
	}
}
