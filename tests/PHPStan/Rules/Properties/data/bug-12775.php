<?php

namespace Bug12775;

/**
 * @property string $comment
 */
class Foo {
	public static string $comment = 'foo';

	public function __get(string $name): mixed {
		return 'bar';
	}
}

$foo = new Foo;
var_dump(Foo::$comment);
