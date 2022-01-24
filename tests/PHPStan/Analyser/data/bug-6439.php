<?php

namespace Bug6439;

use function PHPStan\Testing\assertType;


class HelloWorld
{
	public function unionOnLeft(string $name, ?int $gesperrt = null, ?int $adaid = null):void
	{
		$string = 'general';
		if (null !== $gesperrt) {
			$string = $string . ' branch-a';
		}
		assertType("'general'|'general branch-a'", $string);
		if (null !== $adaid) {
			$string = $string . ' branch-b';
		}
		assertType("'general'|'general branch-a'|'general branch-a branch-b'|'general branch-b'", $string);
	}

	public function unionOnRight(string $name, ?int $gesperrt = null, ?int $adaid = null):void
	{
		$string = 'general';
		if (null !== $gesperrt) {
			$string = 'branch-a ' . $string;
		}
		assertType("'branch-a general'|'general'", $string);
		if (null !== $adaid) {
			$string = 'branch-b ' . $string;
		}
		assertType("'branch-a general'|'branch-b branch-a general'|'branch-b general'|'general'", $string);
	}

	public function testLimit() {
		$string = '0';

		if (rand(0,1)) {
			$string .= 'a';
		}
		if (rand(0,1)) {
			$string .= 'b';
		}
		if (rand(0,1)) {
			$string .= 'c';
		}
		if (rand(0,1)) {
			$string .= 'd';
		}
		if (rand(0,1)) {
			$string .= 'e';

			// union should contain 32 elements
			assertType("'0'|'0a'|'0ab'|'0abc'|'0abcd'|'0abcde'|'0abce'|'0abd'|'0abde'|'0abe'|'0ac'|'0acd'|'0acde'|'0ace'|'0ad'|'0ade'|'0ae'|'0b'|'0bc'|'0bcd'|'0bcde'|'0bce'|'0bd'|'0bde'|'0be'|'0c'|'0cd'|'0cde'|'0ce'|'0d'|'0de'|'0e'", $string);

			// adding more elements should fallback to the more general form
			$string .= 'too-long';
			assertType("literal-string&non-empty-string", $string);
		}
	}
}
