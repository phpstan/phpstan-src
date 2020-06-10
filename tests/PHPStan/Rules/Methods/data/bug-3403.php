<?php

namespace Bug3403;

interface Foo
{

	public function bar(...$baz): void;

}

class AFoo implements Foo
{

	public function bar(...$baz): void
	{

	}

}
