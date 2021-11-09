<?php declare(strict_types=1);

namespace Bug5935;

class Test
{
	/**
	 * @var int[]
	 */
	private $arr = [];

	public function test(int $id): int
	{
		$arr = &$this->arr;
		if(isset($arr[$id]))
		{
			return $arr[$id];
		}
		else
		{
			return $arr[$id] = time();
		}
	}
}

class Test2
{
	/**
	 * @var int[]
	 */
	private $arr;

	public function test(int $id): int
	{
		$arr = &$this->arr;
		if(isset($arr[$id]))
		{
			return $arr[$id];
		}
		else
		{
			return $arr[$id] = time();
		}
	}
}
