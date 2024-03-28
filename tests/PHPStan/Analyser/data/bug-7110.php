<?php

namespace Bug7110;

use UnexpectedValueException;

/**
 * @param mixed[] $arr
 * @phpstan-assert string[] $arr
 */
function validateStringArray(array $arr) : void {
	foreach ($arr as $s) {
		if (!is_string($s)) {
			throw new UnexpectedValueException('Invalid value ' . gettype($s));
		}
	}
}

function takesString(string $s) : void {
	echo $s;
}
function takesInt(int $s) : void {
	echo (string) $s;
}

/**
 * @param mixed[] $arr
 */
function takesArray(array $arr) : void {
	takesInt($arr[0]); // this is fine

	validateStringArray($arr);

	takesInt($arr[0]); // this is an error

	foreach ($arr as $a) {
		takesString($a); // this is fine
	}
}
