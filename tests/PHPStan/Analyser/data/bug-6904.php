<?php declare(strict_types = 1); // onlyif PHP_VERSION_ID >= 80100

namespace Bug6904;

use stdClass;
use function PHPStan\Testing\assertType;

/**
 * @template T
 */
interface Collection
{
}


/**
 * @template T
 */
interface Selectable
{
	/** @return T */
	public function first();
}


class HelloWorld
{
	/**
     * @var Collection<stdClass>&Selectable<stdClass>
     */
    public Collection&Selectable $items;

	/**
     * @param Selectable<TValue> $selectable
     * @return TValue
     *
     * @template TValue
     */
    private function matchOne(Selectable $selectable)
    {
		return $selectable->first();
    }

	public function run(): void
    {
        assertType('stdClass', $this->matchOne($this->items));
    }
}
