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

	/**
	 * @param non-empty-array<mixed> $a
	 */
	public function doBaz(array $a): void
	{

	}

}

/** @template T */
class Bar
{

}
