<?php

namespace Bug4006;

interface Foo
{

	/**
	 * @return never
	 */
	public function bar();

}

class Bar implements Foo
{

	public function bar(): void
	{
		throw new \Exception();
	}

}
