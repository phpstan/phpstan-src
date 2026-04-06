<?php declare(strict_types = 1);

namespace Bug11430Nsrt;

use function PHPStan\Testing\assertType;

/**
 * @template T
 *
 * @implements \IteratorAggregate<T>
 */
abstract class Option  implements \IteratorAggregate
{
    /**
     * @template S
	 * @template U
     *
     * @param S $value     The actual return value.
     * @param U $noneValue The value which should be considered "None"; null by
     *                     default.
     *
     * @return (S is U ? None : Option<S>)
     */
    public static function fromValue($value, $noneValue = null)
    {
        if ($value === $noneValue) {
            return None::create();
		}

        return new Some($value);
    }
}

/**
 * @extends Option<mixed>
 */
final class None extends Option
{
    /** @var None|null */
    private static $instance;

    /**
     * @return None
     */
    public static function create(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

	public function getIterator(): \EmptyIterator
    {
        return new \EmptyIterator();
    }
}

/**
 * @template T
 *
 * @extends Option<T>
 */
final class Some extends Option
{
    /** @var T */
    private $value;

    /**
     * @param T $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

	/**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator([$this->value]);
    }
}


class Test
{
	/** @var Option<string> */
	public Option $name;
}

$test = new Test();
/** @var ?string $foo */
$foo = null;
$test->name = Option::fromValue($foo);
assertType('Bug11430Nsrt\Option<string>', $test->name);
