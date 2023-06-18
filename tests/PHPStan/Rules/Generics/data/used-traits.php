<?php

namespace UsedTraits;

trait NongenericTrait
{

}

/** @template T of object */
trait GenericTrait
{

}

class Foo
{

	/** @use NongenericTrait<\stdClass> */
	use NongenericTrait;

	/** @use GenericTrait<\stdClass> */
	use GenericTrait;

}

class Bar
{

	/** @use GenericTrait<int> */
	use GenericTrait;

}

class Baz
{

	use GenericTrait;

}

class Lorem
{

	/** @use GenericTrait<\stdClass, \Exception> */
	use GenericTrait;

}

trait NestedTrait
{

	/** @use NongenericTrait<int> */
	use GenericTrait;

}

class Ipsum
{

	use NestedTrait;

}

class Dolor
{

	/** @use GenericTrait<covariant \Throwable> */
	use GenericTrait;

}
