<?php declare(strict_types=1);

namespace Bug7519;

use Generator;
use Iterator;
use stdClass;
use FilterIterator;

use function PHPStan\Testing\assertType;

/**
 * @template TKey
 * @template T
 *
 * @extends FilterIterator<TKey, T, Iterator<TKey, T>>
 */
class FooFilterIterator extends FilterIterator
{
	/**
	 * @param Iterator<TKey, T> $iterator
	 */
	public function __construct(Iterator $iterator)
	{
		parent::__construct($iterator);
	}

	public function accept(): bool
	{
		return true;
	}
}

function doFoo() {
	$generator = static function (): Generator {
		yield true => true;
		yield false => false;
		yield new stdClass => new StdClass;
		yield [] => [];
	};

	$iterator = new FooFilterIterator($generator());

	assertType('array{}|bool|stdClass', $iterator->key());
	assertType('array{}|bool|stdClass', $iterator->current());

	$generator = static function (): Generator {
		yield true => true;
		yield false => false;
	};

	$iterator = new FooFilterIterator($generator());

	assertType('bool', $iterator->key());
	assertType('bool', $iterator->current());
}
