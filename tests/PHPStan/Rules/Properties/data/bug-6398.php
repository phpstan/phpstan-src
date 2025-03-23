<?php

namespace Bug6398;

class AsyncTask{
	/**
	 * @phpstan-var \ArrayObject<int, array<string, mixed>>|null
	 */
	private static $threadLocalStorage = null;

	/**
	 * @param mixed  $complexData the data to store
	 */
	protected function storeLocal(string $key, $complexData) : void{
		if(self::$threadLocalStorage === null){
			self::$threadLocalStorage = new \ArrayObject();
		}
		self::$threadLocalStorage[spl_object_id($this)][$key] = $complexData;
	}

	/**
	 * @return mixed
	 */
	protected function fetchLocal(string $key){
		$id = spl_object_id($this);
		if(self::$threadLocalStorage === null or !isset(self::$threadLocalStorage[$id][$key])){
			throw new \InvalidArgumentException("No matching thread-local data found on this thread");
		}

		return self::$threadLocalStorage[$id][$key];
	}
}
