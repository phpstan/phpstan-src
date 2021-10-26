<?php

namespace OverridingFinalConstant;

class Foo
{

	const NON_FINAL = 1;
	final const FOO = 1;
	final const BAR = 1;

}

class Bar extends Foo
{

	const NON_FINAL = 2;
	const FOO = 2;
	final const BAR = 2;

}

interface FooInterface
{

	const FOO = 1;
	final const BAR = 2;

}

class Baz implements FooInterface
{

	const FOO = 3;
	const BAR = 4;

}

interface BarInterface
{

	/** @var int */
	const FOO = 1;

}

class Lorem implements BarInterface
{

	/** @var string */
	const FOO = 1;

}

class Dolor
{

	private const PRIVATE_CONST = 1;
	protected const PROTECTED_CONST = 2;
	public const PUBLIC_CONST = 3;
	const ANOTHER_PUBLIC_CONST = 4;

}

class PrivateDolor extends Dolor
{

	private const PRIVATE_CONST = 1;
	private const PROTECTED_CONST = 2; // error
	private const PUBLIC_CONST = 3; // error
	private const ANOTHER_PUBLIC_CONST = 4; // error

}

class ProtectedDolor extends Dolor
{

	protected const PRIVATE_CONST = 1;
	protected const PROTECTED_CONST = 2;
	protected const PUBLIC_CONST = 3; // error
	protected const ANOTHER_PUBLIC_CONST = 4; // error

}

class PublicDolor extends Dolor
{

	public const PRIVATE_CONST = 1;
	public const PROTECTED_CONST = 2;
	public const PUBLIC_CONST = 3;
	public const ANOTHER_PUBLIC_CONST = 4;

}

class Public2Dolor extends Dolor
{

	const PRIVATE_CONST = 1;
	const PROTECTED_CONST = 2;
	const PUBLIC_CONST = 3;
	const ANOTHER_PUBLIC_CONST = 4;

}
