<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug12420;

/**
 * @template V
 */
class Collection
{
    /**
     * @var array<V>
     */
    private array $array;

	/**
     * @param array<V> $a
     */
    final public function __construct(array $a = [])
    {
        $this->array = $a;
    }

	/**
     * @return array<V>
     */
    public function toArray(): array
    {
        return $this->array;
    }
	
	// ...
}

enum Code: string
{
    case FOO = 'foo';
    case BAR = 'bar';
}


class Test
{
	
	/**
	 * This works.
	 *
	 * @return array<value-of<Code>>
	 */
	public static function testArray(): array
	{
		return [
			Code::FOO->value,
			Code::BAR->value,
		];
	}
	
	/**
	 * This fails as expectd.
	 *
	 * @return array<value-of<Code>>
	 */
	public static function testFailingArray(): array
	{
		return [
			Code::FOO->value,
			Code::BAR->value,
			'wrong',
		];
	}
	
	/**
	 * FIXME This fails because the type infered from the constructor call is `Collection<string>`.
	 *
	 * @return Collection<value-of<Code>>
	 */
	public static function testCollection(): Collection
	{
		return new Collection([
			Code::FOO->value,
			Code::BAR->value,
		]);
	}
}
