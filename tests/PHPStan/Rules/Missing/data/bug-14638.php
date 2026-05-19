<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14638;

interface CachedValueInterface
{
	public function getValue(): mixed;
}

interface CacheItemInterface {}

interface AdapterInterface {}

interface ItemInterface extends CacheItemInterface {}

/**
 * @template T
 */
interface CallbackInterface
{
	/**
	 * @return T
	 */
	public function __invoke(CacheItemInterface $item, bool &$save): mixed;
}

interface CacheInterface
{
	/**
	 * @template T
	 *
	 * @param (callable(CacheItemInterface,bool):T)|(callable(ItemInterface,bool):T)|CallbackInterface<T> $callback
	 *
	 * @return T
	 */
	public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed;
}

class PhpArrayAdapter implements CacheInterface
{
	/** @var array<string, int> */
	private array $keys;
	/** @var array<int, mixed> */
	private array $values;

	public function __construct(private readonly AdapterInterface $pool) {}

	public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
	{
		if (!isset($this->values)) {
			$this->initialize();
		}
		if (!isset($this->keys[$key])) {
			get_from_pool:
			if ($this->pool instanceof CacheInterface) {
				return $this->pool->get($key, $callback, $beta, $metadata);
			}

			return $this->doGet($this->pool, $key, $callback, $beta, $metadata);
		}
		$value = $this->values[$this->keys[$key]];

		if ('N;' === $value) {
			return null;
		}
		if (!$value instanceof CachedValueInterface) {
			return $value;
		}
		try {
			return $value->getValue();
		} catch (\Throwable) {
			unset($this->keys[$key]);
			goto get_from_pool;
		}
	}

	private function initialize(): void
	{
		$this->keys = [];
		$this->values = [];
	}

	/**
	 * @param array<string, mixed> &$metadata
	 */
	private function doGet(AdapterInterface $pool, string $key, callable $callback, ?float $beta, ?array &$metadata = null): mixed
	{
		return null;
	}
}
