<?php // lint >= 8.0

namespace Bug15700Methods;

class Foo
{

	/**
	 * @template TIn of object
	 * @param TIn $object
	 * @return \WeakReference<TIn>
	 */
	public static function create(object $object): \WeakReference
	{
		return \WeakReference::create($object);
	}

}

/**
 * @param mixed $value
 */
function doFoo($value): void
{
	\WeakReference::create($value);
	Foo::create($value);
}
