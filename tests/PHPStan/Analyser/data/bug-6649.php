<?php

namespace Bug6649;

use function PHPStan\Testing\assertType;

class Foo {}
interface Bar {}

class FooBar extends Foo implements Bar {}

/**
 * @template TKey of Foo
 */
class Collection {}

/**
 * @template TKey of Foo&Bar
 * @extends Collection<TKey>
 */
class SubCollection extends Collection {
    /** @param TKey $key */
	public function __construct($key) {
		assertType('TKey of Bug6649\Bar&Bug6649\Foo (class Bug6649\SubCollection, argument)', $key);
	}

	public static function test(): void {
		assertType('Bug6649\SubCollection<Bug6649\FooBar>', new SubCollection(new FooBar()));
	}
}
