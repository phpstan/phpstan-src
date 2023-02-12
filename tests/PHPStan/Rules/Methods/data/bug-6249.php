<?php // lint >= 7.4

namespace Bug6249N1;

interface EntityInterface
{
	public function getId(): string;
}

use Countable;
use IteratorAggregate;

/**
 * @template TKey of array-key
 * @template T
 * @template-extends IteratorAggregate<TKey, T>
 */
interface CollectionInterface extends Countable, IteratorAggregate
{
}

namespace Bug6249N2;

use ArrayIterator;
use InvalidArgumentException;
use IteratorIterator;
use Bug6249N1\EntityInterface;
use Traversable;
/**
 * @extends \IteratorIterator<array-key, EntityInterface, Traversable<array-key, EntityInterface>>
 */
final class Eii extends IteratorIterator
{
	/**
	 * @param iterable<array-key, EntityInterface> $iterable
	 */
	public function __construct(iterable $iterable)
	{
		parent::__construct($iterable instanceof Traversable ? $iterable : new ArrayIterator($iterable));
	}

	/**
	 * @return EntityInterface
	 */
	public function current()
	{
		$current = parent::current();

		if (!$current instanceof EntityInterface) {
			throw new InvalidArgumentException(sprintf('Item "%s" must be an instance of "%s".', gettype($current), EntityInterface::class));
		}

		return $current;
	}

	/**
	 * return ?string
	 */
	public function key()
	{
		if ($this->valid()) {
			/** @var EntityInterface $current */
			$current = $this->current();

			return $current->getId();
		}

		return null;
	}
}

namespace Bug6249N3;

use ArrayIterator;
use Countable;
use Bug6249N1\CollectionInterface;
use Traversable;

/**
 * @template TKey of array-key
 * @template T
 * @implements CollectionInterface<TKey, T>
 */
final class Cw implements CollectionInterface
{
	/**
	 * @var iterable<TKey, T>
	 */
	private iterable $iterable;

	/**
	 * @param iterable<TKey, T> $iterable
	 */
	private function __construct(iterable $iterable)
	{
		$this->iterable = $iterable;
	}

	/**
	 * @param iterable<TKey, T> $iterable
	 *
	 * @return self<TKey, T>
	 */
	public static function fromIterable(iterable $iterable): self
	{
		return new self($iterable);
	}

	/**
	 * @template UKey of array-key
	 * @template U
	 * @param iterable<UKey, U> $iterable
	 *
	 * @return self<UKey, U>
	 */
	public static function fromIterableCorrect(iterable $iterable): self
	{
		return new self($iterable);
	}

	public function count(): int
	{
		if (is_array($this->iterable) || $this->iterable instanceof Countable) {
			return count($this->iterable);
		}

		return count(iterator_to_array($this->iterable, false));
	}

	public function getIterator(): Traversable
	{
		if (is_array($this->iterable)) {
			return new ArrayIterator($this->iterable);
		}

		return $this->iterable;
	}
}

class Foo
{

	public function doFoo()
	{
		\Bug6249N3\Cw::fromIterable(new \Bug6249N2\Eii([]));
		\Bug6249N3\Cw::fromIterableCorrect(new \Bug6249N2\Eii([]));
	}

}
