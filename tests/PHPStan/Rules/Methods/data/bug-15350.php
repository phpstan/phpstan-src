<?php

namespace Bug15350;

/**
 * @phpstan-type BareArr array
 */
class Foo
{

	/**
	 * @param callable(array): array $a
	 */
	public function inline($a): void
	{
	}

	/**
	 * @param callable(BareArr): BareArr $a
	 */
	public function alias($a): void
	{
	}

}
