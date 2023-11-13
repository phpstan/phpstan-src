<?php

namespace ConflictingTraitConstantsTypes;

trait Foo
{

	public const int|string FOO_CONST = 1;

	public const BAR_CONST = 1;

}

class Bar
{

	use Foo;

	public const int|string FOO_CONST = 1;

}

class Baz
{

	use Foo;

	public const int FOO_CONST = 1;

	public const int BAR_CONST = 1;

}

class Lorem
{

	use Foo;

	public const FOO_CONST = 1;

}
