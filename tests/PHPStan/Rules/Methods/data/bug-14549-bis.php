<?php

namespace Bug14549Bis;

class Foo
{

	/** @param array<int> $param */
	public function callArrayInt(array $param): void
	{
	}

	/** @param array{string, string} $param */
	public function callConstantArrayStringString(array $param): void
	{
	}

	/** @param array{object|string, string} $param */
	public function callConstantArrayObjectOrStringString(array $param): void
	{
	}

	/** @param array{object|string, string, string} $param */
	public function callConstantArrayObjectOrStringStringString(array $param): void
	{
	}

	/**
	 * @param callable-array $task
	 */
	public function doCallWithCallableArray(array $task): void
	{
		$this->callArrayInt($task);
		$this->callConstantArrayStringString($task);
		$this->callConstantArrayObjectOrStringString($task);
		$this->callConstantArrayObjectOrStringStringString($task);
	}

	/**
	 * @param callable&array $task
	 */
	public function doCallWithCallableAndArray(array $task): void
	{
		$this->callArrayInt($task);
		$this->callConstantArrayStringString($task);
		$this->callConstantArrayObjectOrStringString($task);
		$this->callConstantArrayObjectOrStringStringString($task);
	}

	/** @param array<string> $param */
	public function callArrayString(array $param): void
	{
	}

	public function doCallWithHasOffsetValue(array $arr): void
	{
		if (isset($arr[1]) && $arr[1] === 1) {
			$this->callArrayString($arr);
			$this->callArrayInt($arr);
		}
	}

}
