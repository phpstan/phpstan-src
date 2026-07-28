<?php // lint >= 7.4

declare(strict_types = 1);

namespace Bug13253;

use Generator;
use ReflectionFunction;
use function PHPStan\Testing\assertType;

/**
 * @template TKey
 * @template TValue
 */
class Pfline
{

	/** @var iterable<TKey, TValue> */
	private iterable $data;

	/** @param iterable<TKey, TValue> $data */
	public function __construct(iterable $data)
	{
		$this->data = $data;
	}

	/**
	 * @return iterable<TKey, TValue>
	 */
	public function data(): iterable
	{
		return $this->data;
	}

	/**
	 * @template TMapKey
	 * @template TMapValue
	 * @param null|callable(TValue): Generator<TMapKey, TMapValue> $func
	 * @phpstan-self-out self<TMapKey, TMapValue>
	 * @return self<TMapKey, TMapValue>
	 */
	public function map(?callable $func = null): self
	{
		return $this;
	}

}

function (): void {
	$reflection = new ReflectionFunction('strlen');
	$params = $reflection->getParameters();

	// Without chaining (each call advances the type through @phpstan-self-out)
	$pipeline = new Pfline($params);
	$pipeline->map(function ($param) {
		assertType('ReflectionParameter', $param);
		yield $param;
	});
	$pipeline->map(function ($param) {
		assertType('ReflectionParameter', $param);
		yield $param->getName();
	});
	$pipeline->map(function ($param) {
		assertType('non-empty-string', $param);
		yield substr_count('.', $param);
	});

	// With chaining (the type must advance through @return self<TMapKey, TMapValue>)
	$pipeline = new Pfline($params);
	$pipeline->map(function ($param) {
		assertType('ReflectionParameter', $param);
		yield $param;
	})->map(function ($param) {
		assertType('ReflectionParameter', $param);
		yield $param->getName();
	})->map(function ($param) {
		assertType('non-empty-string', $param);
		yield substr_count('.', $param);
	});
};
