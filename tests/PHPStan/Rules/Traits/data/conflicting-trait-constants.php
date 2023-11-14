<?php // lint >= 8.2

namespace ConflictingTraitConstants;

trait Foo
{

	public const PUBLIC_CONSTANT = 1;

	protected const PROTECTED_CONSTANT = 1;

	private const PRIVATE_CONSTANT = 1;

	final public const PUBLIC_FINAL_CONSTANT = 1;

}

class Bar
{

	use Foo;

	protected const PUBLIC_CONSTANT = 1;

}

class Bar2
{

	use Foo;

	private const PUBLIC_CONSTANT = 1;

}

class Bar3
{

	use Foo;

	public const PROTECTED_CONSTANT = 1;

}

class Bar4
{

	use Foo;

	private const PROTECTED_CONSTANT = 1;

}

class Bar5
{

	use Foo;

	protected const PRIVATE_CONSTANT = 1;

}

class Bar6
{

	use Foo;

	public const PRIVATE_CONSTANT = 1;

}

class Bar7
{

	use Foo;

	public const PUBLIC_FINAL_CONSTANT = 1;

}

class Bar8
{

	use Foo;

	final public const PUBLIC_CONSTANT = 1;

}


class Bar9
{

	use Foo;

	public const PUBLIC_CONSTANT = 2;

}

class Bar10
{
	use Foo;

	final public const PUBLIC_FINAL_CONSTANT = 1;
}
