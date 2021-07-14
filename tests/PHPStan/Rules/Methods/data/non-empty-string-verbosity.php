<?php

namespace NonEmptyStringVerbosity;

class Foo
{

	/**
	 * @param non-empty-string $s
	 */
	public function doFoo(string $s): void
	{
		$this->doBar($s);
	}

	public function doBar(int $i): void
	{

	}

}
