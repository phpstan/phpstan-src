<?php

namespace PregFilter;


use function PHPStan\Testing\assertType;

class Foo {
	function doFoo() {
		$subject = array('1', 'a', '2', 'b', '3', 'A', 'B', '4');
		$pattern = array('/\d/', '/[a-z]/', '/[1a]/');
		$replace = array('A:$0', 'B:$0', 'C:$0');

		assertType('array<int, string>', preg_filter($pattern, $replace, $subject));
	}

	function doFoo1() {
		$subject = array('1', 'a', '2', 'b', '3', 'A', 'B', '4');
		assertType('array<int, string>', preg_filter('/\d/', '$0', $subject));

		$subject = 'hallo';
		assertType('string|null', preg_filter('/\d/', '$0', $subject));
	}

	function doFoo2() {
		$subject = 123;
		assertType('string|null', preg_filter('/\d/', '$0', $subject));

		$subject = 123.123;
		assertType('string|null', preg_filter('/\d/', '$0', $subject));
	}

	public function dooFoo3(string $pattern, string $replace) {
		assertType('list<string>|string|null', preg_filter($pattern, $replace));
		assertType('list<string>|string|null', preg_filter($pattern));
		assertType('list<string>|string|null', preg_filter());
	}

	function bug664() {
		assertType('string|null', preg_filter(['#foo#'], ['bar'], 'subject'));

		assertType('array<int, string>', preg_filter(['#foo#'], ['bar'], ['subject']));
	}
}
