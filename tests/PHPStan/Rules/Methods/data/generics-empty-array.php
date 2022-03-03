<?php

namespace GenericsEmptyArrayCall;

class Foo
{

	/**
	 * @template TKey of array-key
	 * @template T
	 * @param array<TKey, T> $a
	 * @return array{TKey, T}
	 */
	public function doFoo(array $a = []): array
	{

	}

	public function doBar()
	{
		$this->doFoo();
		$this->doFoo([]);
	}

}
