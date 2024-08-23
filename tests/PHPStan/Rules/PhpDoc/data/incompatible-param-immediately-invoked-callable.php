<?php

namespace IncompatibleParamImmediatelyInvokedCallable;

class Foo
{

	/**
	 * @param-immediately-invoked-callable $a
	 * @param-later-invoked-callable $b
	 */
	public function doFoo(callable $a, callable $b): void
	{

	}

	/**
	 * @param-immediately-invoked-callable $b
	 * @param-later-invoked-callable $c
	 */
	public function doBar(callable $a): void
	{

	}

	/**
	 * @param-immediately-invoked-callable $a
	 * @param-immediately-invoked-callable $b
	 */
	public function doBaz(string $a, int $b): void
	{

	}

	/**
	 * @param-later-invoked-callable $a
	 * @param-later-invoked-callable $b
	 */
	public function doBaz2(string $a, int $b): void
	{

	}

}

/**
 * @param-immediately-invoked-callable $a
 * @param-later-invoked-callable $b
 */
function doFoo(callable $a, callable $b): void
{

}

/**
 * @param-immediately-invoked-callable $b
 * @param-later-invoked-callable $c
 */
function doBar(callable $a): void
{

}

/**
 * @param-immediately-invoked-callable $a
 * @param-immediately-invoked-callable $b
 */
function doBaz(string $a, int $b): void
{

}

/**
 * @param-later-invoked-callable $a
 * @param-later-invoked-callable $b
 */
function doBaz2(string $a, int $b): void
{

}
