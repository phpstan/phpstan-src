<?php

namespace Bug10653;

use stdClass;
use Exception;

class A
{
	private int $Counter = 0;

	/**
	 * @return stdClass
	 */
	public function getMayFail()
	{
		return $this->throwOnFailure($this->mayFail());
	}

	/**
	 * @template T
	 *
	 * @param T $result
	 * @return (T is false ? never : T)
	 */
	public function throwOnFailure($result)
	{
		if ($result === false) {
			throw new Exception('Operation failed');
		}
		return $result;
	}

	/**
	 * @return stdClass|false
	 */
	public function mayFail()
	{
		$this->Counter++;
		return $this->Counter % 2 ? new stdClass() : false;
	}
}
