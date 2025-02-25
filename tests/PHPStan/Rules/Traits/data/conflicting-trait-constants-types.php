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

trait SelfRef
{

	public const int SR_CONST = self::SR_CONST;

}

class SelfRefOverride
{

	use SelfRef;

	public const int SR_CONST = 1;

}

class SelfRefWrongType
{

	use SelfRef;

	public const int SR_CONST = [1];

}

class SelfRefExt
{

	use SelfRef;

	public const int SR_CONST = self::SR_CONST;

}

class SelfRefExtWrong
{

	use SelfRef;

	public const string SR_CONST = self::SR_CONST;

}

class SelfRefExtOverride
{

	use SelfRefExt;

	public const int SR_CONST = 1;

}

class SelfRefExtOverrideExt
{

	use SelfRefExtOverride;

	public const int SR_CONST = self::SR_CONST;

}