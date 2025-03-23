<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug8929;

class Test
{
	/** @var \WeakMap<object, mixed> */
	protected readonly \WeakMap $cache;

	public function __construct()
	{
		$this->cache = new \WeakMap();
	}

	public function add(object $key, mixed $value): void
	{
		$this->cache[$key] = $value;
	}
}
