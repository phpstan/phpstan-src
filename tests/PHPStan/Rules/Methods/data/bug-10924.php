<?php

declare(strict_types=1);

namespace Bug10924;

use function array_key_exists;
use function spl_object_id;

/**
 * @phpstan-template T of object
 * @phpstan-implements \IteratorAggregate<int, T>
 */
final class ObjectSet implements \IteratorAggregate
{
	/**
	 * @var object[]
	 * @phpstan-var array<int, T>
	 */
	private array $objects = [];

	/**
	 * @phpstan-param T ...$objects
	 */
	public function __construct(object ...$objects)
	{
		foreach ($objects as $object) {
			$this->objects[spl_object_id($object)] = $object;
		}
	}

	/** @phpstan-param T ...$objects */
	public function add(object ...$objects): void
	{
		foreach ($objects as $object) {
			$this->objects[spl_object_id($object)] = $object;
		}
	}

	/** @phpstan-param T ...$objects */
	public function remove(object ...$objects): void
	{
		foreach ($objects as $object) {
			unset($this->objects[spl_object_id($object)]);
		}
	}

	public function clear(): void
	{
		$this->objects = [];
	}

	public function contains(object $object): bool
	{
		return array_key_exists(spl_object_id($object), $this->objects);
	}

	/** @phpstan-return \ArrayIterator<int, T> */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->objects);
	}

	/**
	 * @return object[]
	 * @phpstan-return array<int, T>
	 */
	public function toArray(): array
	{
		return $this->objects;
	}
}

/**
 * @phpstan-type CollectPromise object
 */
class TimingsHandler
{
	/** @phpstan-var ObjectSet<\Closure(bool $enable) : void> */
	private static ?ObjectSet $toggleCallbacks = null;
	/** @phpstan-var ObjectSet<\Closure() : void> */
	private static ?ObjectSet $resetCallbacks = null;
	/** @phpstan-var ObjectSet<\Closure() : list<CollectPromise>> */
	private static ?ObjectSet $collectCallbacks = null;

	/**
	 * @phpstan-return ObjectSet<\Closure(bool $enable) : void>
	 */
	public static function getToggleCallbacks(): ObjectSet
	{
		return self::$toggleCallbacks ??= new ObjectSet();
	}

	/**
	 * @phpstan-return ObjectSet<\Closure() : void>
	 */
	public static function getResetCallbacks(): ObjectSet
	{
		return self::$resetCallbacks ??= new ObjectSet();
	}

	/**
	 * @phpstan-return ObjectSet<\Closure() : list<CollectPromise>>
	 */
	public static function getCollectCallbacks(): ObjectSet
	{
		return self::$collectCallbacks ??= new ObjectSet();
	}

}
