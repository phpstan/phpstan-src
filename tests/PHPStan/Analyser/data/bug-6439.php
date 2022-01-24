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
		$string = 'i';

		if (rand(0,1)) {
			$string .= ' a';
		}
		if (rand(0,1)) {
			$string .= ' b';
		}
		if (rand(0,1)) {
			$string .= ' c';
		}
		if (rand(0,1)) {
			$string .= ' d';
		}
		if (rand(0,1)) {
			$string .= ' e';
		}
		assertType("'i'|'i a'|'i a b'|'i a b c'|'i a b c d'|'i a b c d e'|'i a b c e'|'i a b d'|'i a b d e'|'i a b e'|'i a c'|'i a c d'|'i a c d e'|'i a c e'|'i a d'|'i a d e'|'i a e'|'i b'|'i b c'|'i b c d'|'i b c d e'|'i b c e'|'i b d'|'i b d e'|'i b e'|'i c'|'i c d'|'i c d e'|'i c e'|'i d'|'i d e'|'i e'", $string);

		if (rand(0,1)) {
			$string .= ' f';
		}

		assertType("literal-string&non-empty-string", $string);

	}
}
