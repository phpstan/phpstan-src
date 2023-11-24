<?php

namespace YieldTypeRuleTest;

class Foo
{

	/**
	 * @return \Generator<string, int>
	 */
	public function doFoo(): \Generator
	{
		yield 'foo' => 1;
		yield 'foo' => 'bar';
		yield;
		yield 1;
		yield 'foo';
	}

	/**
	 * @return \Generator<array{0: \DateTime, 1: \DateTime, 2: \stdClass, 4: \DateTimeImmutable}>
	 */
	public function doArrayShape(): \Generator
	{
		yield [
			new \DateTime(),
			new \DateTime(),
			new \stdClass,
			new \DateTimeImmutable('2019-10-26'),
		];
	}

}

/**
 * @template TKey
 * @template TValue
 */
final class Map
{
	/** @var TKey */
	private $key;
	/** @var TValue */
	private $value;

	/**
	 * @param TKey $key
	 * @param TValue $value
	 */
	public function __construct($key, $value)
	{
		$this->key = $key;
		$this->value = $value;
	}

	/** @return TKey */
	public function key()
	{
		return $this->key;
	}

	/** @return TValue */
	public function value()
	{
		return $this->value;
	}
}

class TestMap
{

	/**
	 * @template TKey
	 * @template TValue
	 *
	 * @param iterable<TKey, TValue> $iterator
	 * @param callable(TValue, TKey):(TValue|Map<TKey, TValue>) $callback
	 *
	 * @return iterable<TKey, TValue>
	 */
	function iterator_map(iterable $iterator, callable $callback): iterable
	{
		foreach ($iterator as $key => $value) {
			$result = $callback($value, $key);
			if ($result instanceof Map) {
				yield $result->key() => $result->value();
				continue;
			}
			yield $key => $result;
		}
	}

	/**
	 * @template T
	 * @template K
	 * @param iterable<K, T> $hash
	 * @return \Generator<int, K|T, void, void>
	 */
	function hash_to_alt_sequence(iterable $hash): iterable
	{
		foreach ($hash as $k => $v) {
			yield $k;
			yield $v;
		}
	}

	/**
	 * @template T
	 * @template K
	 * @param iterable<K, T> $hash
	 * @return iterable<int, K|T>
	 */
	function hash_to_alt_sequence2(iterable $hash): iterable
	{
		foreach ($hash as $k => $v) {
			yield $k;
			yield $v;
		}
	}

}

/**
 * @return \Generator<int, int, int, void>
 */
function yieldWithIntSendType(){
	yield 1;
	$something = yield 1;
	var_dump(yield 1);
}

/**
 * @return \Generator<int, int, void, void>
 */
function yieldWithVoidSendType(){
	yield 1;
	$something = yield 1;
	var_dump(yield 1);
}

/**
 * @return \Generator<int, int>
 */
function yieldWithoutSendType(){
	yield 1;
	$something = yield 1;
	var_dump(yield 1);
}

/**
 * @return \Generator<array{opt?: int, req: int}>
 */
function yieldNamedArrayShape(): \Generator
{
	yield ['req' => 1, 'foo' => 1];
	yield rand()
		? ['req' => 1, 'opt' => 1]
		: ['req' => 1, 'foo' => 1];
}
