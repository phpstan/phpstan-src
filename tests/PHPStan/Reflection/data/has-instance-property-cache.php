<?php

namespace HasInstancePropertyCache;

class Foo
{

	public static int $staticProp = 1;

}

class Bar
{

	public static int $staticProp = 1;

}

/**
 * @phpstan-require-extends Bar
 */
interface RequiresBar
{

}
