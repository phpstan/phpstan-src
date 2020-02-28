<?php

namespace InvalidPHPStanDoc;

class Baz{}
/** @phpstan-extens Baz */
class Boo extends Baz
{
	/**
	 * @phpstan-template T
	 * @phpstan-pararm class-string<T> $a
	 * @phpstan-return T
	 */
	function foo(string $a){}
}
