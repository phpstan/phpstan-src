<?php

namespace Bug15350;

/**
 * @template T
 */
class Box
{

}

/**
 * @phpstan-type BareArr array
 * @phpstan-type BareBox Box
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

	/**
	 * @param callable(Box): Box $a
	 */
	public function inlineGeneric($a): void
	{
	}

	/**
	 * @param callable(BareBox): BareBox $a
	 */
	public function aliasGeneric($a): void
	{
	}

}
