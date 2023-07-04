<?php

namespace Bug7649;

class Foo
{
	public readonly string $bar;

	public function __construct(bool $flag)
	{
		if ($flag) {
			$this->bar = 'baz';
		} else {
		}
	}
}
