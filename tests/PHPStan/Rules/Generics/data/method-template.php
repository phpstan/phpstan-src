<?php

namespace MethodTemplateType;

class Foo
{

	/**
	 * @template stdClass
	 */
	public function doFoo()
	{

	}

	/**
	 * @template T of Zazzzu
	 */
	public function doBar()
	{

	}

}

/**
 * @template T of \Exception
 * @template Z
 */
class Bar
{

	/**
	 * @template T
	 * @template U
	 */
	public function doFoo()
	{

	}

}

class Baz
{

	/**
	 * @template T of float
	 */
	public function doFoo()
	{

	}

}

/**
 * @phpstan-type ExportedAlias string
 */
class Lorem
{

	/**
	 * @template TypeAlias
	 */
	public function doFoo()
	{

	}

}

/**
 * @phpstan-type LocalAlias string
 * @phpstan-import-type ExportedAlias from Lorem as ImportedAlias
 */
class Ipsum
{

	/**
	 * @template LocalAlias
	 * @template ExportedAlias
	 * @template ImportedAlias
	 */
	public function doFoo()
	{

	}

}

/**
 * @template-covariant T
 */
class Dolor
{

}

class Sit
{

	/**
	 * @template T of Dolor<int>
	 * @template U of Dolor<covariant int>
	 * @template V of Dolor<*>
	 * @template W of Dolor<contravariant int>
	 */
	public function doSit()
	{

	}

}
