<?php

namespace DeepInspectTypes;

class Foo
{

	/**
	 * @param array<iterable> $foo
	 */
	public function doFoo(array $foo): void
	{

	}

	/** @param array<Bar> $bars */
	public function doBar(array $bars): void
	{

	}

}

/** @template T */
class Bar
{

}
