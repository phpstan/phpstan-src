<?php

namespace Bug4498;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param iterable<TKey, TValue> $iterable
	 *
	 * @return iterable<TKey, TValue>
	 *
	 * @template TKey
	 * @template TValue
	 */
	public function fcn(iterable $iterable): iterable
	{
		if ($iterable instanceof \Traversable) {
			assertType('iterable<TKey (method Bug4498\Foo::fcn(), argument), TValue (method Bug4498\Foo::fcn(), argument)>&Traversable', $iterable);
			return $iterable;
		}

		assertType('array<TKey (method Bug4498\Foo::fcn(), argument), TValue (method Bug4498\Foo::fcn(), argument)>', $iterable);

		return $iterable;
	}

	/**
	 * @param iterable<TKey, TValue> $iterable
	 *
	 * @return iterable<TKey, TValue>
	 *
	 * @template TKey
	 * @template TValue
	 */
	public function bar(iterable $iterable): iterable
	{
		if (is_array($iterable)) {
			assertType('array<((int&TKey (method Bug4498\Foo::bar(), argument))|(string&TKey (method Bug4498\Foo::bar(), argument))), TValue (method Bug4498\Foo::bar(), argument)>', $iterable);
			return $iterable;
		}

		assertType('Traversable<TKey (method Bug4498\Foo::bar(), argument), TValue (method Bug4498\Foo::bar(), argument)>', $iterable);

		return $iterable;
	}

}
