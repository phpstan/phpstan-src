<?php // lint >= 8.1

namespace ClassLevelUsedTraits;

trait NongenericTrait
{

}

/** @template T of object */
trait GenericTrait
{

}

/** @use NongenericTrait<\stdClass> */
class Foo
{

	use NongenericTrait;

}

/** @use GenericTrait<int> */
class Bar
{

	use GenericTrait;

}

/** @use GenericTrait<\stdClass> */
class Ok
{

	use GenericTrait;

}

/** @use GenericTrait<\stdClass> */
class NoTraits
{

}

/** @use NongenericTrait<\stdClass> */
class WrongTrait
{

	use GenericTrait;

}

/** @use GenericTrait<\stdClass, \Exception> */
class TooManyTypes
{

	use GenericTrait;

}

/** @use GenericTrait<covariant \Throwable> */
class CallSiteVariance
{

	use GenericTrait;

}

/**
 * @template T
 * @use GenericTrait<T>
 */
trait NestedTrait
{

	use GenericTrait;

}

/** @use GenericTrait<\stdClass> */
enum SomeEnum
{

	use GenericTrait;

}

/** @use GenericTrait<\stdClass> */
interface SomeInterface
{

}

namespace ClassLevelUsedTraitsAlias;

use ClassLevelUsedTraits\GenericTrait as AliasedGenericTrait;

/** @use AliasedGenericTrait<int> */
class InvalidBound
{

	use AliasedGenericTrait;

}
