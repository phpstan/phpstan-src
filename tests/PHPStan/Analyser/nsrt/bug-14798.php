<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14798;

use function PHPStan\Testing\assertType;

enum TestEnum :int{
	case Val1 = 1;
	case Val2 = 2;
}

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 */
class Collection {
	/**
	 * @param array<TKey, TValue> $items
	 */
	public function __construct(private array $items) {	
	}
	
	/**
     * @template TMapValue
     *
     * @param  callable(TValue, TKey): TMapValue  $callback
     * @return static<TKey, TMapValue>
     */
    public function map(callable $callback)
    {
        return new self(array_map($callback, $this->items));
    }

	}

class MondayMorning
{
	public function startDay(): void
	{
		/** @var Collection<int, array{val1: value-of<TestEnum>, val2: string}> $array */
		$array = new Collection([[
			"val1" => TestEnum::Val1->value,
			"val2" => "abc",
		]]);

		$merged = $array->map(static fn (array $a): array => array_merge($a, ["cosi" => "foo"]));

		assertType('Bug14798\Collection<int, array{val1: 1|2, val2: string}>', $array);
		assertType('Bug14798\Collection<int, array{val1: 1|2, val2: string, cosi: \'foo\'}>', $merged);
	}
}
