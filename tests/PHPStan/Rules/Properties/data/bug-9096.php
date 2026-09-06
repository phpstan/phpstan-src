<?php // lint >= 7.4

declare(strict_types = 1);

namespace Bug9096;

use ArrayIterator;
use EmptyIterator;
use IteratorAggregate;
use function PHPStan\Testing\assertType;

/**
 * @template T
 * @implements IteratorAggregate<T>
 */
abstract class Option implements IteratorAggregate
{

	/**
	 * @template S
	 * @param S $value
	 * @param S $noneValue
	 * @return Option<S>
	 */
	public static function fromValue($value, $noneValue = null)
	{
		if ($value === $noneValue) {
			return None::create();
		}

		return new Some($value);
	}

}

/** @extends Option<mixed> */
final class None extends Option
{

	/** @var None|null */
	private static $instance;

	public static function create(): self
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function getIterator(): EmptyIterator
	{
		return new EmptyIterator();
	}

}

/**
 * @template T
 * @extends Option<T>
 */
final class Some extends Option
{

	/** @var T */
	private $value;

	/** @param T $value */
	public function __construct($value)
	{
		$this->value = $value;
	}

	/** @return ArrayIterator<int, T> */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator([$this->value]);
	}

}

class Test
{

	/** @var Option<string|null> */
	public Option $name;

}

$test = new Test();
$test->name = Option::fromValue('test');
assertType('Option<string|null>', $test->name);
